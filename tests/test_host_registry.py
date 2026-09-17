import json
import tempfile
import unittest
from ops import host_registry
from pathlib import Path

from ops.host_registry import load_registry


class HostRegistryTests(unittest.TestCase):

    def test_load_cs_and_it(self):
        registry = {
            "schema_version": 1,
            "hosts": [
                {
                    "code": "cs",
                    "name": "Computer Science",
                    "ip": "192.0.2.11",
                    "ssh_user": "hostingportal",
                    "enabled": True
                },
                {
                    "code": "it",
                    "name": "Information Technology",
                    "ip": "192.0.2.12",
                    "ssh_user": "hostingportal",
                    "enabled": True
                }
            ]
        }

        with tempfile.TemporaryDirectory() as tmp:
            path = Path(tmp) / "hosts.json"
            path.write_text(json.dumps(registry))

            hosts = load_registry(path)

            self.assertEqual(
                [host["code"] for host in hosts],
                ["cs", "it"]
            )
    def _load_document(self, document):
        with tempfile.TemporaryDirectory() as tmp:
            path = Path(tmp) / "hosts.json"
            path.write_text(
                json.dumps(document),
                encoding="utf-8"
            )
            return load_registry(path)

    def test_reject_invalid_schema_version(self):
        registry = {
            "schema_version": 2,
            "hosts": []
        }

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

    def test_reject_duplicate_host_code(self):
        host = {
            "code": "cs",
            "name": "Computer Science",
            "ip": "192.0.2.11",
            "ssh_user": "hostingportal",
            "enabled": True
        }

        registry = {
            "schema_version": 1,
            "hosts": [host, host.copy()]
        }

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

    def test_reject_path_traversal_code(self):
        registry = {
            "schema_version": 1,
            "hosts": [
                {
                    "code": "../etc",
                    "name": "Invalid Host",
                    "ip": "192.0.2.11",
                    "ssh_user": "hostingportal",
                    "enabled": True
                }
            ]
        }

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

    def _valid_host(self, **overrides):
        host = {
            "code": "cs",
            "name": "Computer Science",
            "ip": "192.0.2.11",
            "ssh_user": "hostingportal",
            "enabled": True,
        }
        host.update(overrides)
        return host

    def _registry(self, hosts):
        return {
            "schema_version": 1,
            "hosts": hosts,
        }

    def test_reject_invalid_ip(self):
        registry = self._registry([
            self._valid_host(ip="example.org")
        ])

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

    def test_reject_unauthorized_ssh_user(self):
        registry = self._registry([
            self._valid_host(ssh_user="root")
        ])

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

    def test_reject_reserved_host_codes(self):
        for code in ("status", "metadata"):
            with self.subTest(code=code):
                registry = self._registry([
                    self._valid_host(code=code)
                ])

                with self.assertRaises(host_registry.RegistryError):
                    self._load_document(registry)

    def test_reject_unexpected_fields(self):
        registry = self._registry([
            self._valid_host(password="placeholder")
        ])

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

    def test_reject_boolean_schema_version(self):
        registry = {
            "schema_version": True,
            "hosts": [],
        }

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

    def test_reject_invalid_hosts_type(self):
        registry = {
            "schema_version": 1,
            "hosts": {"cs": self._valid_host()},
        }

        with self.assertRaises(ValueError):
            self._load_document(registry)

    def test_reject_invalid_host_name(self):
        for name in ("", "   ", 123, None):
            with self.subTest(name=name):
                registry = self._registry([
                    self._valid_host(name=name)
                ])

                with self.assertRaises(ValueError):
                    self._load_document(registry)

    def test_reject_invalid_enabled_type(self):
        registry = self._registry([
            self._valid_host(enabled="true")
        ])

        with self.assertRaises(ValueError):

            self._load_document(registry)

    def test_disabled_host_is_preserved(self):
        registry = self._registry([
            self._valid_host(enabled=False)
        ])

        hosts = self._load_document(registry)

        self.assertEqual(len(hosts), 1)
        self.assertIs(hosts[0]["enabled"], False)

    def test_reject_missing_hosts(self):
        registry = {
            "schema_version": 1
        }

        with self.assertRaises(ValueError):
            self._load_document(registry)

    def test_reject_invalid_top_level(self):
        with self.assertRaises(ValueError):
            self._load_document([])

    def test_reject_non_object_host(self):
        registry = self._registry([
            "invalid-host"
        ])

        with self.assertRaises(ValueError):
            self._load_document(registry)

    def test_reject_missing_host_field(self):
        host = self._valid_host()
        del host["ssh_user"]

        registry = self._registry([host])

        with self.assertRaises(ValueError):
            self._load_document(registry)

    def test_reject_malformed_json(self):
        with tempfile.TemporaryDirectory() as tmp:
            path = Path(tmp) / "hosts.json"
            path.write_text(
                '{"schema_version": 1, "hosts": [',
                encoding="utf-8"
            )

            with self.assertRaises(host_registry.RegistryError):
                load_registry(path)

    def test_registry_error_contract(self):
        self.assertTrue(
            hasattr(host_registry, "RegistryError"),
            "RegistryError class is required",
        )

        self.assertTrue(
            issubclass(
                host_registry.RegistryError,
                ValueError,
            )
        )

        registry = {
            "schema_version": 99,
            "hosts": [],
        }

        with self.assertRaises(host_registry.RegistryError):
            self._load_document(registry)

if __name__ == "__main__":
    unittest.main()
