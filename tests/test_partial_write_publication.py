"""Partial cache publication safety tests."""

import copy
import json
import tempfile
import unittest

from datetime import datetime, timezone
from pathlib import Path
from unittest.mock import patch

from ops.status_fetcher import write_atomic


class PartialWritePublicationTests(unittest.TestCase):

    def test_partial_write_never_reports_success(self):
        from ops.multi_host_entrypoint import main

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"
            metadata_dir = cache_dir / "metadata"

            metadata_dir.mkdir(parents=True)

            registry = json.loads(
                Path("config/hosts.example.json").read_text(
                    encoding="utf-8"
                )
            )

            for host in registry["hosts"]:
                host["enabled"] = host["code"] == "cs"

            registry_path.write_text(
                json.dumps(registry),
                encoding="utf-8",
            )

            cs_path = cache_dir / "cs.json"
            legacy_path = cache_dir / "status.json"
            metadata_path = metadata_dir / "cs.json"

            old_cache = b"OLD_VALID_CACHE"

            cs_path.write_bytes(old_cache)
            legacy_path.write_bytes(old_cache)

            old_success = "2026-09-17T02:00:00+00:00"

            write_atomic(
                metadata_path,
                {
                    "host_code": "cs",
                    "last_attempt": old_success,
                    "last_success": old_success,
                    "fetch_state": "SUCCESS",
                    "error_category": None,
                },
            )

            payload = {
                "schema_version": 1,
                "generated_at": "2026-09-18T09:00:00+07:00",
                "overall_status": "HEALTHY",
                "services": {},
                "storage": {},
                "summary": {},
                "backup": {},
                "students": [],
            }

            observed_states = []

            def forbidden_process(*args, **kwargs):
                raise AssertionError("Real process must not start")

            def fake_fetch(host, *, process_factory):
                self.assertIs(
                    process_factory,
                    forbidden_process,
                )
                return copy.deepcopy(payload)

            def fake_write(destination, document):
                destination = Path(destination)

                if destination in (cs_path, legacy_path):
                    metadata = json.loads(
                        metadata_path.read_text(
                            encoding="utf-8"
                        )
                    )
                    observed_states.append(
                        (destination.name, metadata["fetch_state"])
                    )

                if destination == legacy_path:
                    raise OSError("SIMULATED_PARTIAL_WRITE")

                return write_atomic(destination, document)

            with patch(
                "ops.ssh_adapter.fetch_status",
                side_effect=fake_fetch,
            ), patch(
                "ops.multi_host_fetcher.write_atomic",
                side_effect=fake_write,
            ):
                exit_code = main(
                    [
                        "--registry",
                        str(registry_path),
                        "--cache-dir",
                        str(cache_dir),
                    ],
                    process_factory=forbidden_process,
                    now=lambda: datetime(
                        2026, 9, 18, 2, 0,
                        tzinfo=timezone.utc,
                    ),
                )

            self.assertEqual(exit_code, 1)

            self.assertNotEqual(
                cs_path.read_bytes(),
                old_cache,
            )

            self.assertEqual(
                legacy_path.read_bytes(),
                old_cache,
            )

            # Publication marker must precede both writes.
            self.assertEqual(
                observed_states,
                [
                    ("cs.json", "PUBLISHING"),
                    ("status.json", "PUBLISHING"),
                ],
            )

            final_metadata = json.loads(
                metadata_path.read_text(encoding="utf-8")
            )

            self.assertNotEqual(
                final_metadata["fetch_state"],
                "SUCCESS",
            )

            self.assertEqual(
                final_metadata["last_success"],
                old_success,
            )


if __name__ == "__main__":
    unittest.main()
