import json
import tempfile
import unittest
from pathlib import Path

from ops.status_fetcher import (
    StatusValidationError,
    validate_status,
    write_atomic,
)


VALID = {
    "schema_version": 1,
    "generated_at": "2026-09-01T16:30:00+07:00",
    "overall_status": "HEALTHY",
    "services": {
        "nginx": True,
        "php_fpm": True,
        "mariadb": True,
        "ssh": True,
        "ufw": True,
    },
    "storage": {
        "root": {"used_percent": 11},
        "student": {"used_percent": 1},
    },
    "summary": {
        "students": 10,
        "enabled": 10,
        "suspended": 0,
        "warnings": 0,
    },
    "backup": {
        "last_backup": None,
        "size_bytes": None,
    },
    "students": [],
}


class StatusFetcherTest(unittest.TestCase):

    def test_validate_status_accepts_schema_v1(self):
        result = validate_status(VALID)
        self.assertEqual(result["schema_version"], 1)

    def test_validate_status_rejects_wrong_schema(self):
        invalid = {
            **VALID,
            "schema_version": 2,
        }

        with self.assertRaises(StatusValidationError):
            validate_status(invalid)

    def test_validate_status_rejects_missing_required_key(self):
        invalid = dict(VALID)
        del invalid["summary"]

        with self.assertRaises(StatusValidationError):
            validate_status(invalid)

    def test_write_atomic_creates_valid_json(self):
        with tempfile.TemporaryDirectory() as tmp:
            destination = Path(tmp) / "status.json"

            write_atomic(
                destination,
                VALID,
            )

            loaded = json.loads(
                destination.read_text(
                    encoding="utf-8"
                )
            )

            self.assertEqual(
                loaded,
                VALID,
            )

            self.assertFalse(
                (Path(tmp) / "status.json.tmp").exists()
            )


if __name__ == "__main__":
    unittest.main()
