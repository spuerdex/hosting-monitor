import copy
import json
import tempfile
import unittest

from datetime import datetime, timezone
from pathlib import Path


class MultiHostFetcherTests(unittest.TestCase):

    def test_cs_and_it_fetch_success(self):

        # Import inside the test so missing feature is
        # reported by this test, not during collection.
        from ops.multi_host_fetcher import fetch_all

        with tempfile.TemporaryDirectory() as tmp:

            root = Path(tmp)
            registry_path = root / "hosts.json"
            cache_dir = root / "cache"

            # Use example data, never production registry.
            registry = json.loads(
                Path("config/hosts.example.json").read_text()
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
                    "generated_at": "2026-09-18T09:00:00+07:00",
                    "overall_status": status,
                    "services": {
                        "nginx": True,
                        "php_fpm": True,
                        "mariadb": True,
                        "ssh": True,
                        "ufw": True,
                    },
                    "storage": {
                        "root": {"used_percent": 1},
                        "student": {"used_percent": 1},
                    },
                    "summary": {
                        "students": 0,
                        "enabled": 0,
                        "suspended": 0,
                        "warnings": 0,
                    },
                    "backup": {
                        "last_backup": None,
                        "size_bytes": None,
                    },
                    "students": [],
                }

            payloads = {
                "cs": make_status("HEALTHY"),
                "it": make_status("WARNING"),
            }

            calls = []

            # Mock adapter: no subprocess or SSH.
            def fake_fetch(host):
                calls.append(host["code"])
                return copy.deepcopy(payloads[host["code"]])

            fetch_all(
                registry_path=registry_path,
                cache_dir=cache_dir,
                fetch=fake_fetch,
                now=lambda: datetime(
                    2026, 9, 18, 2, 0,
                    tzinfo=timezone.utc,
                ),
            )

            # Both hosts must be fetched exactly once.
            self.assertEqual(
                sorted(calls),
                ["cs", "it"],
            )

            cs = json.loads(
                (cache_dir / "cs.json").read_text()
            )

            it = json.loads(
                (cache_dir / "it.json").read_text()
            )

            legacy = json.loads(
                (cache_dir / "status.json").read_text()
            )

            self.assertEqual(
                cs["overall_status"],
                "HEALTHY",
            )

            self.assertEqual(
                it["overall_status"],
                "WARNING",
            )

            # Legacy cache belongs to CS only.
            self.assertEqual(legacy, cs)

            self.assertTrue(
                (cache_dir / "metadata" / "cs.json").exists()
            )

            self.assertTrue(
                (cache_dir / "metadata" / "it.json").exists()
            )

    def test_it_failure_does_not_block_cs(self):
        from ops.multi_host_fetcher import fetch_all

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

            def make_status(status):
                return {
                    "schema_version": 1,
                    "generated_at": "2026-09-18T09:00:00+07:00",
                    "overall_status": status,
                    "services": {},
                    "storage": {},
                    "summary": {
                        "students": 0,
                        "enabled": 0,
                        "suspended": 0,
                        "warnings": 0,
                    },
                    "backup": {},
                    "students": [],
                }

            old_it = make_status("HEALTHY")
            new_cs = make_status("WARNING")

            # Existing IT cache must survive failure.
            (cache_dir / "it.json").write_text(
                json.dumps(old_it),
                encoding="utf-8",
            )

            calls = []

            def fake_fetch(host):
                code = host["code"]
                calls.append(code)

                if code == "it":
                    raise RuntimeError(
                        "private failure detail"
                    )

                return copy.deepcopy(new_cs)

            # Fetch must continue despite IT failure.
            results = fetch_all(
                registry_path=registry_path,
                cache_dir=cache_dir,
                fetch=fake_fetch,
                now=lambda: datetime(
                    2026, 9, 18, 2, 0,
                    tzinfo=timezone.utc,
                ),
            )

            self.assertEqual(calls, ["cs", "it"])

            cs = json.loads(
                (cache_dir / "cs.json").read_text()
            )

            it = json.loads(
                (cache_dir / "it.json").read_text()
            )

            legacy = json.loads(
                (cache_dir / "status.json").read_text()
            )

            self.assertEqual(cs, new_cs)
            self.assertEqual(it, old_it)
            self.assertEqual(legacy, new_cs)

            self.assertEqual(
                results["it"],
                "UNREACHABLE",
            )

            metadata = json.loads(
                (
                    cache_dir / "metadata" / "it.json"
                ).read_text()
            )

            self.assertEqual(
                metadata["fetch_state"],
                "UNREACHABLE",
            )

            # Never expose raw exception details.
            self.assertNotIn(
                "private failure detail",
                json.dumps(metadata),
            )


    def test_cs_failure_preserves_last_success(self):
        from ops.multi_host_fetcher import fetch_all

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
                host["enabled"] = True

            registry_path.write_text(
                json.dumps(registry),
                encoding="utf-8",
            )

            old_cs = {
                "schema_version": 1,
                "generated_at": "2026-09-18T08:00:00+07:00",
                "overall_status": "HEALTHY",
                "services": {},
                "storage": {},
                "summary": {},
                "backup": {},
                "students": [],
            }

            new_it = copy.deepcopy(old_cs)
            new_it["overall_status"] = "WARNING"

            for filename in ("cs.json", "status.json"):
                (cache_dir / filename).write_text(
                    json.dumps(old_cs),
                    encoding="utf-8",
                )

            previous_success = "2026-09-18T01:00:00+00:00"

            (metadata_dir / "cs.json").write_text(
                json.dumps({
                    "host_code": "cs",
                    "last_attempt": previous_success,
                    "last_success": previous_success,
                    "fetch_state": "SUCCESS",
                    "error_category": None,
                }),
                encoding="utf-8",
            )

            calls = []

            def fake_fetch(host):
                code = host["code"]
                calls.append(code)

                if code == "cs":
                    raise RuntimeError("private CS failure")

                return copy.deepcopy(new_it)

            results = fetch_all(
                registry_path=registry_path,
                cache_dir=cache_dir,
                fetch=fake_fetch,
                now=lambda: datetime(
                    2026, 9, 18, 2, 0,
                    tzinfo=timezone.utc,
                ),
            )

            self.assertEqual(calls, ["cs", "it"])
            self.assertEqual(results["cs"], "UNREACHABLE")
            self.assertEqual(results["it"], "WARNING")

            for filename in ("cs.json", "status.json"):
                saved = json.loads(
                    (cache_dir / filename).read_text()
                )
                self.assertEqual(saved, old_cs)

            saved_it = json.loads(
                (cache_dir / "it.json").read_text()
            )
            self.assertEqual(saved_it, new_it)

            metadata = json.loads(
                (metadata_dir / "cs.json").read_text()
            )

            self.assertEqual(
                metadata["last_success"],
                previous_success,
            )

            self.assertEqual(
                metadata["last_attempt"],
                "2026-09-18T02:00:00+00:00",
            )

            self.assertEqual(
                metadata["fetch_state"],
                "UNREACHABLE",
            )

            self.assertNotIn(
                "private CS failure",
                json.dumps(metadata),
            )



    def test_invalid_registry_changes_nothing(self):
        from ops.host_registry import RegistryError
        from ops.multi_host_fetcher import fetch_all

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            registry = root / "hosts.json"
            cache = root / "cache"
            cache.mkdir()

            legacy = cache / "status.json"
            legacy.write_text(
                "ORIGINAL_CACHE",
                encoding="utf-8",
            )

            registry.write_text(
                json.dumps({
                    "schema_version": 99,
                    "hosts": [],
                }),
                encoding="utf-8",
            )

            calls = []

            def fake_fetch(host):
                calls.append(host)
                raise AssertionError("Fetcher must not run")

            with self.assertRaises(RegistryError):
                fetch_all(
                    registry_path=registry,
                    cache_dir=cache,
                    fetch=fake_fetch,
                    now=lambda: datetime(
                        2026, 9, 18, 2, 0,
                        tzinfo=timezone.utc,
                    ),
                )

            self.assertEqual(calls, [])

            self.assertEqual(
                legacy.read_text(encoding="utf-8"),
                "ORIGINAL_CACHE",
            )

            self.assertEqual(
                sorted(p.name for p in cache.iterdir()),
                ["status.json"],
            )



    def test_oversized_payload_preserves_cache(self):
        from ops.multi_host_fetcher import fetch_all

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
                host["enabled"] = host["code"] == "cs"

            registry_path.write_text(
                json.dumps(registry),
                encoding="utf-8",
            )

            old_cache = {
                "schema_version": 1,
                "generated_at": "2026-09-18T08:00:00+07:00",
                "overall_status": "HEALTHY",
                "services": {},
                "storage": {},
                "summary": {},
                "backup": {},
                "students": [],
            }

            for filename in ("cs.json", "status.json"):
                (cache_dir / filename).write_text(
                    json.dumps(old_cache),
                    encoding="utf-8",
                )

            oversized = copy.deepcopy(old_cache)

            # Simulated oversized data, never real student data.
            oversized["unexpected_data"] = "X" * 1048577

            self.assertGreater(
                len(json.dumps(oversized).encode("utf-8")),
                1024 * 1024,
            )

            calls = []

            def fake_fetch(host):
                calls.append(host["code"])
                return copy.deepcopy(oversized)

            results = fetch_all(
                registry_path=registry_path,
                cache_dir=cache_dir,
                fetch=fake_fetch,
                now=lambda: datetime(
                    2026, 9, 18, 2, 0,
                    tzinfo=timezone.utc,
                ),
            )

            self.assertEqual(calls, ["cs"])

            self.assertEqual(
                results["cs"],
                "UNREACHABLE",
            )

            for filename in ("cs.json", "status.json"):
                saved = json.loads(
                    (cache_dir / filename).read_text(
                        encoding="utf-8"
                    )
                )

                self.assertEqual(saved, old_cache)

            metadata = json.loads(
                (
                    cache_dir / "metadata" / "cs.json"
                ).read_text(encoding="utf-8")
            )

            self.assertEqual(
                metadata["fetch_state"],
                "UNREACHABLE",
            )

            self.assertNotIn(
                "unexpected_data",
                json.dumps(metadata),
            )


if __name__ == "__main__":
    unittest.main()
