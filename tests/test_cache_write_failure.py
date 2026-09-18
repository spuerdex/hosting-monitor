import copy
import json
import tempfile
import unittest

from datetime import datetime, timezone
from pathlib import Path
from unittest.mock import patch

from ops.status_fetcher import write_atomic


class CacheWriteFailureTests(unittest.TestCase):

    def test_cache_write_failure_aborts_without_false_success(self):
        from ops.multi_host_entrypoint import main

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"
            cache_dir.mkdir()

            registry = json.loads(
                Path("config/hosts.example.json").read_text(
                    encoding="utf-8"
                )
            )

            for host in registry["hosts"]:
                host["enabled"] = True

            registry_path.write_text(
                json.dumps(registry),
                encoding="utf-8",
            )

            old_cache = b"OLD_VALID_CACHE"

            for filename in ("cs.json", "status.json"):
                (cache_dir / filename).write_bytes(old_cache)

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

            calls = []

            def fake_fetch(host, *, process_factory):
                calls.append(host["code"])
                return copy.deepcopy(payload)

            def fake_write(destination, document):
                if Path(destination) == (cache_dir / "cs.json"):
                    raise OSError("SIMULATED_DISK_FAILURE")

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
                    process_factory=lambda *a, **kw: None,
                    now=lambda: datetime(
                        2026, 9, 18, 2, 0,
                        tzinfo=timezone.utc,
                    ),
                )

            self.assertEqual(exit_code, 1)
            self.assertEqual(calls, ["cs"])

            for filename in ("cs.json", "status.json"):
                self.assertEqual(
                    (cache_dir / filename).read_bytes(),
                    old_cache,
                )

            metadata_path = (
                cache_dir / "metadata" / "cs.json"
            )

            metadata = json.loads(
                metadata_path.read_text(encoding="utf-8")
            )

            self.assertEqual(
                metadata["fetch_state"],
                "PUBLISHING",
            )

            self.assertIsNone(
                metadata["last_success"],
            )

            self.assertFalse(
                (cache_dir / "it.json").exists()
            )


if __name__ == "__main__":
    unittest.main()
