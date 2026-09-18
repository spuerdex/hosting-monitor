import tempfile
import unittest

from pathlib import Path

from ops.host_registry import RegistryError


class MultiHostCLITests(unittest.TestCase):

    def run_cli(self, results=None, error=None):
        from ops.multi_host_cli import cli_main

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)

            registry_path = root / "hosts.json"
            cache_dir = root / "cache"

            calls = []

            def fake_execute(**kwargs):
                calls.append(kwargs)

                if error is not None:
                    raise error

                return results

            exit_code = cli_main(
                [
                    "--registry",
                    str(registry_path),
                    "--cache-dir",
                    str(cache_dir),
                ],
                execute=fake_execute,
            )

            self.assertEqual(len(calls), 1)

            self.assertEqual(
                calls[0]["registry_path"],
                registry_path,
            )

            self.assertEqual(
                calls[0]["cache_dir"],
                cache_dir,
            )

            return exit_code

    def test_all_hosts_success_exit_zero(self):
        self.assertEqual(
            self.run_cli({
                "cs": "HEALTHY",
                "it": "WARNING",
            }),
            0,
        )

    def test_it_failure_exit_zero(self):
        self.assertEqual(
            self.run_cli({
                "cs": "HEALTHY",
                "it": "UNREACHABLE",
            }),
            0,
        )

    def test_cs_failure_exit_zero(self):
        self.assertEqual(
            self.run_cli({
                "cs": "UNREACHABLE",
                "it": "WARNING",
            }),
            0,
        )

    def test_all_hosts_failure_exit_one(self):
        self.assertEqual(
            self.run_cli({
                "cs": "UNREACHABLE",
                "it": "UNREACHABLE",
            }),
            1,
        )

    def test_invalid_registry_exit_two(self):
        self.assertEqual(
            self.run_cli(
                error=RegistryError(
                    "invalid registry"
                ),
            ),
            2,
        )

    def test_no_enabled_hosts_exit_two(self):
        self.assertEqual(
            self.run_cli({}),
            2,
        )



    def test_unexpected_runner_error_exit_one(self):
        import io
        from contextlib import redirect_stdout, redirect_stderr

        stdout = io.StringIO()
        stderr = io.StringIO()

        with redirect_stdout(stdout), redirect_stderr(stderr):
            exit_code = self.run_cli(
                error=RuntimeError("PRIVATE_ERROR_DETAIL"),
            )

        self.assertEqual(exit_code, 1)

        self.assertNotIn(
            "PRIVATE_ERROR_DETAIL",
            stdout.getvalue() + stderr.getvalue(),
        )



    def test_unknown_status_does_not_exit_zero(self):
        self.assertEqual(
            self.run_cli({
                "cs": "UNKNOWN",
                "it": "UNKNOWN",
            }),
            1,
        )


if __name__ == "__main__":
    unittest.main()
