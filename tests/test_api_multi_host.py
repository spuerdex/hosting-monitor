import json
import tempfile
import unittest
from datetime import datetime, timezone
from pathlib import Path
from unittest.mock import patch


def status(overall):
    return {
        "schema_version": 1,
        "generated_at": "2026-09-30T12:00:00Z",
        "overall_status": overall,
        "services": {},
        "storage": {},
        "summary": {},
        "backup": {},
        "students": [],
    }


class ApiMultiHostTests(unittest.TestCase):
    def test_api_registry_is_accepted_and_each_host_uses_api_provider(self):
        from ops.multi_host_entrypoint import run

        registry = {
            "schema_version": 2,
            "hosts": [
                {
                    "code": "cs",
                    "name": "Computer Science",
                    "base_url": "http://127.0.0.1:9001/api",
                    "enabled": True,
                    "timeout_seconds": 3,
                    "api_token_env": "CS_TOKEN",
                },
                {
                    "code": "it",
                    "name": "Information Technology",
                    "base_url": "http://127.0.0.1:9002/api",
                    "enabled": True,
                    "timeout_seconds": 3,
                    "api_token_env": "IT_TOKEN",
                },
            ],
        }

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"
            registry_path.write_text(json.dumps(registry), encoding="utf-8")

            calls = []

            def fetch_api(host):
                calls.append(host["code"])
                return status("HEALTHY")

            with patch(
                "ops.api_provider.fetch_status",
                side_effect=fetch_api,
            ):
                results = run(
                    registry_path=registry_path,
                    cache_dir=cache_dir,
                    process_factory=None,
                    now=lambda: datetime(
                        2026, 9, 30, 12, 0, tzinfo=timezone.utc
                    ),
                )

            self.assertEqual(calls, ["cs", "it"])
            self.assertEqual(results, {"cs": "HEALTHY", "it": "HEALTHY"})
            saved_cs = json.loads(
                (cache_dir / "cs.json").read_text(encoding="utf-8")
            )
            self.assertEqual(saved_cs["host_code"], "cs")


if __name__ == "__main__":
    unittest.main()
