"""Multi-host executable bootstrap tests."""

import json
import runpy
import subprocess
import sys
import tempfile
import unittest

from pathlib import Path
from unittest.mock import patch


class CLIBootstrapTests(unittest.TestCase):

    def test_module_exits_two_without_enabled_hosts(self):

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
                host["enabled"] = False

            registry_path.write_text(
                json.dumps(registry),
                encoding="utf-8",
            )

            argv = [
                "multi_host_entrypoint",
                "--registry",
                str(registry_path),
                "--cache-dir",
                str(cache_dir),
            ]

            def forbidden_process(*args, **kwargs):
                raise AssertionError(
                    "Real process must not start"
                )

            with patch.object(sys, "argv", argv), patch.object(
                subprocess,
                "Popen",
                side_effect=forbidden_process,
            ):
                with self.assertRaises(SystemExit) as result:
                    runpy.run_path(
                        str(
                            Path(__file__).resolve().parents[1]
                            / "ops"
                            / "multi_host_entrypoint.py"
                        ),
                        run_name="__main__",
                    )

            self.assertEqual(
                result.exception.code,
                2,
            )



    def test_bootstrap_fetches_enabled_hosts_with_mock(self):

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

            self.assertEqual(
                [h["code"] for h in registry["hosts"]],
                ["cs", "it"],
            )

            registry_path.write_text(
                json.dumps(registry),
                encoding="utf-8",
            )

            def status(value):
                return {
                    "schema_version": 1,
                    "generated_at": "2026-09-18T09:00:00+07:00",
                    "overall_status": value,
                    "services": {},
                    "storage": {},
                    "summary": {},
                    "backup": {},
                    "students": [],
                }

            payloads = {
                "cs": status("HEALTHY"),
                "it": status("WARNING"),
            }

            calls = []

            def forbidden_process(*args, **kwargs):
                raise AssertionError("REAL_PROCESS_FORBIDDEN")

            def fake_fetch(host, *, process_factory):
                self.assertIs(
                    process_factory,
                    guarded_popen,
                )

                calls.append(host["code"])

                return json.loads(
                    json.dumps(payloads[host["code"]])
                )

            argv = [
                "multi_host_entrypoint",
                "--registry",
                str(registry_path),
                "--cache-dir",
                str(cache_dir),
            ]

            with patch.object(
                sys, "argv", argv
            ), patch(
                "subprocess.Popen",
                side_effect=forbidden_process,
            ) as guarded_popen, patch(
                "ops.ssh_adapter.fetch_status",
                side_effect=fake_fetch,
            ):
                with self.assertRaises(SystemExit) as result:
                    runpy.run_path(
                        str(
                            Path(__file__).resolve().parents[1]
                            / "ops"
                            / "multi_host_entrypoint.py"
                        ),
                        run_name="__main__",
                    )

            self.assertEqual(result.exception.code, 0)
            self.assertEqual(calls, ["cs", "it"])

            guarded_popen.assert_not_called()

            for code in ("cs", "it"):
                saved = json.loads(
                    (cache_dir / f"{code}.json").read_text(
                        encoding="utf-8"
                    )
                )

                self.assertEqual(saved, payloads[code])

                metadata = json.loads(
                    (
                        cache_dir / "metadata" / f"{code}.json"
                    ).read_text(encoding="utf-8")
                )

                self.assertEqual(
                    metadata["fetch_state"],
                    "SUCCESS",
                )

            legacy = json.loads(
                (cache_dir / "status.json").read_text(
                    encoding="utf-8"
                )
            )

            self.assertEqual(legacy, payloads["cs"])


if __name__ == "__main__":
    unittest.main()
