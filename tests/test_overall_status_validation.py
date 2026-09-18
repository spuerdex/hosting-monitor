"""Validate host status before cache publication."""

import copy
import json
import tempfile
import unittest

from datetime import datetime, timezone
from pathlib import Path

from ops.multi_host_fetcher import fetch_all
from ops.status_fetcher import write_atomic


class OverallStatusValidationTests(unittest.TestCase):

    def test_unknown_status_preserves_existing_host_cache(self):

        with tempfile.TemporaryDirectory() as tmp:

            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"
            metadata_dir = cache_dir / "metadata"

            metadata_dir.mkdir(parents=True)

            # Use the existing safe example registry.
            registry = json.loads(
                Path("config/hosts.example.json").read_text(
                    encoding="utf-8"
                )
            )

            for host in registry["hosts"]:
                host["enabled"] = True

            self.assertEqual(
                [host["code"] for host in registry["hosts"]],
                ["cs", "it"],
            )

            registry_path.write_text(
                json.dumps(registry),
                encoding="utf-8",
            )

            def make_status(status):
                return {
                    "schema_version": 1,
                    "generated_at":
                        "2026-09-18T09:00:00+07:00",
                    "overall_status": status,
                    "services": {},
                    "storage": {},
                    "summary": {},
                    "backup": {},
                    "students": [],
                }

            cs_status = make_status("HEALTHY")
            invalid_it_status = make_status("UNKNOWN")

            # Seed a previous successful IT cache.
            old_it_status = make_status("WARNING")
            old_it_status["generated_at"] = (
                "2026-09-17T09:00:00+07:00"
            )

            it_cache = cache_dir / "it.json"

            write_atomic(
                it_cache,
                old_it_status,
            )

            old_success = "2026-09-17T02:00:00+00:00"

            it_metadata_path = metadata_dir / "it.json"

            write_atomic(
                it_metadata_path,
                {
                    "host_code": "it",
                    "last_attempt": old_success,
                    "last_success": old_success,
                    "fetch_state": "SUCCESS",
                    "error_category": None,
                },
            )

            calls = []

            def fake_fetch(host):
                code = host["code"]
                calls.append(code)

                if code == "cs":
                    return copy.deepcopy(cs_status)

                if code == "it":
                    return copy.deepcopy(invalid_it_status)

                raise AssertionError("Unexpected host")

            results = fetch_all(
                registry_path=registry_path,
                cache_dir=cache_dir,
                fetch=fake_fetch,
                now=lambda: datetime(
                    2026, 9, 18, 2, 0,
                    tzinfo=timezone.utc,
                ),
            )

            # Both hosts must be attempted independently.
            self.assertEqual(calls, ["cs", "it"])

            # CS succeeds normally.
            self.assertEqual(
                results["cs"],
                "HEALTHY",
            )

            self.assertEqual(
                json.loads(
                    (cache_dir / "cs.json").read_text(
                        encoding="utf-8"
                    )
                ),
                cs_status,
            )

            # Unknown IT status must be rejected.
            self.assertEqual(
                results["it"],
                "UNREACHABLE",
            )

            # Last valid IT cache must survive.
            self.assertEqual(
                json.loads(
                    it_cache.read_text(encoding="utf-8")
                ),
                old_it_status,
            )

            # Reject invalid payload without claiming success.
            metadata = json.loads(
                it_metadata_path.read_text(
                    encoding="utf-8"
                )
            )

            self.assertEqual(
                metadata["fetch_state"],
                "UNREACHABLE",
            )

            self.assertEqual(
                metadata["error_category"],
                "FETCH_FAILED",
            )

            self.assertEqual(
                metadata["last_success"],
                old_success,
            )

            # CS compatibility cache stays correct.
            self.assertEqual(
                json.loads(
                    (cache_dir / "status.json").read_text(
                        encoding="utf-8"
                    )
                ),
                cs_status,
            )


if __name__ == "__main__":
    unittest.main()
