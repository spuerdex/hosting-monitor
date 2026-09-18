import copy
import json
import tempfile
import unittest

from datetime import datetime, timezone
from pathlib import Path
from unittest.mock import patch


class MultiHostEntrypointTests(unittest.TestCase):

    def test_entrypoint_fetches_cs_and_it(self):
        from ops.multi_host_entrypoint import run

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)

            registry_path = root / "hosts.json"
            cache_dir = root / "cache"

            example = Path(
                "config/hosts.example.json"
            )

            registry = json.loads(
                example.read_text(encoding="utf-8")
            )

            for host in registry["hosts"]:
                host["enabled"] = True

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

            payloads = {
                "cs": make_status("HEALTHY"),
                "it": make_status("WARNING"),
            }

            # Sentinel: never starts a real process.
            def forbidden_process(*args, **kwargs):
                raise AssertionError(
                    "Real process must not start"
                )

            def fake_fetch_status(
                host,
                *,
                process_factory,
            ):
                self.assertIs(
                    process_factory,
                    forbidden_process,
                )

                return copy.deepcopy(
                    payloads[host["code"]]
                )

            # Patch the actual SSH adapter.
            with patch(
                "ops.ssh_adapter.fetch_status",
                side_effect=fake_fetch_status,
            ) as mock_fetch:

                results = run(
                    registry_path=registry_path,
                    cache_dir=cache_dir,
                    process_factory=forbidden_process,
                    now=lambda: datetime(
                        2026, 9, 18, 2, 0,
                        tzinfo=timezone.utc,
                    ),
                )

            self.assertEqual(
                [
                    call.args[0]["code"]
                    for call in mock_fetch.call_args_list
                ],
                ["cs", "it"],
            )

            self.assertEqual(
                results["cs"],
                "HEALTHY",
            )

            self.assertEqual(
                results["it"],
                "WARNING",
            )

            def read_cache(filename):
                return json.loads(
                    (cache_dir / filename).read_text(
                        encoding="utf-8"
                    )
                )

            self.assertEqual(
                read_cache("cs.json"),
                payloads["cs"],
            )

            self.assertEqual(
                read_cache("it.json"),
                payloads["it"],
            )

            self.assertEqual(
                read_cache("status.json"),
                payloads["cs"],
            )

            for code in ("cs", "it"):
                self.assertTrue(
                    (
                        cache_dir
                        / "metadata"
                        / f"{code}.json"
                    ).exists()
                )



    def test_it_failure_does_not_block_cs(self):
        from ops.multi_host_entrypoint import run

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"

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

            cs_status = {
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

            def forbidden_process(*args, **kwargs):
                raise AssertionError("Real process must not start")

            def fake_fetch(host, *, process_factory):
                self.assertIs(
                    process_factory,
                    forbidden_process,
                )

                code = host["code"]
                calls.append(code)

                if code == "it":
                    raise TimeoutError("private SSH detail")

                return copy.deepcopy(cs_status)

            with patch(
                "ops.ssh_adapter.fetch_status",
                side_effect=fake_fetch,
            ):
                results = run(
                    registry_path=registry_path,
                    cache_dir=cache_dir,
                    process_factory=forbidden_process,
                    now=lambda: datetime(
                        2026, 9, 18, 2, 0,
                        tzinfo=timezone.utc,
                    ),
                )

            self.assertEqual(calls, ["cs", "it"])

            self.assertEqual(results["cs"], "HEALTHY")
            self.assertEqual(results["it"], "UNREACHABLE")

            for filename in ("cs.json", "status.json"):
                saved = json.loads(
                    (cache_dir / filename).read_text(
                        encoding="utf-8"
                    )
                )
                self.assertEqual(saved, cs_status)

            self.assertFalse(
                (cache_dir / "it.json").exists()
            )

            metadata = json.loads(
                (
                    cache_dir / "metadata" / "it.json"
                ).read_text(encoding="utf-8")
            )

            self.assertEqual(
                metadata["fetch_state"],
                "UNREACHABLE",
            )

            self.assertNotIn(
                "private SSH detail",
                json.dumps(metadata),
            )



    def test_cli_main_wires_entrypoint(self):
        from ops.multi_host_entrypoint import main

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"

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

            def forbidden_process(*args, **kwargs):
                raise AssertionError(
                    "Real process must not start"
                )

            def fake_fetch(host, *, process_factory):
                self.assertIs(
                    process_factory,
                    forbidden_process,
                )
                calls.append(host["code"])
                return copy.deepcopy(payload)

            with patch(
                "ops.ssh_adapter.fetch_status",
                side_effect=fake_fetch,
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

            self.assertEqual(exit_code, 0)
            self.assertEqual(calls, ["cs", "it"])

            self.assertTrue(
                (cache_dir / "cs.json").exists()
            )

            self.assertTrue(
                (cache_dir / "it.json").exists()
            )

            self.assertTrue(
                (cache_dir / "status.json").exists()
            )



    def test_all_hosts_failure_exit_one(self):
        from ops.multi_host_entrypoint import main

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"

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

            calls = []

            def forbidden_process(*args, **kwargs):
                raise AssertionError("Real process must not start")

            def fake_fetch(host, *, process_factory):
                self.assertIs(
                    process_factory,
                    forbidden_process,
                )
                calls.append(host["code"])
                raise TimeoutError("PRIVATE_SSH_FAILURE")

            with patch(
                "ops.ssh_adapter.fetch_status",
                side_effect=fake_fetch,
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

            self.assertEqual(calls, ["cs", "it"])
            self.assertEqual(exit_code, 1)

            for filename in (
                "cs.json",
                "it.json",
                "status.json",
            ):
                self.assertFalse(
                    (cache_dir / filename).exists()
                )

            for code in ("cs", "it"):
                metadata_path = (
                    cache_dir / "metadata" / f"{code}.json"
                )

                metadata = json.loads(
                    metadata_path.read_text(
                        encoding="utf-8"
                    )
                )

                self.assertEqual(
                    metadata["fetch_state"],
                    "UNREACHABLE",
                )

                self.assertNotIn(
                    "PRIVATE_SSH_FAILURE",
                    json.dumps(metadata),
                )


if __name__ == "__main__":
    unittest.main()
