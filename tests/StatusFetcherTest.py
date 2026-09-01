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


import os
from unittest.mock import Mock

from ops.status_fetcher import fetch_and_store


class FetchAndStoreTest(unittest.TestCase):

    def test_write_atomic_sets_mode_0640(self):
        with tempfile.TemporaryDirectory() as tmp:
            destination = Path(tmp) / "status.json"

            write_atomic(
                destination,
                VALID,
            )

            mode = (
                destination.stat().st_mode
                & 0o777
            )

            self.assertEqual(
                mode,
                0o640,
            )

    def test_fetch_and_store_validates_and_saves_json(self):
        with tempfile.TemporaryDirectory() as tmp:
            destination = Path(tmp) / "status.json"

            fake_runner = Mock()

            fake_runner.return_value = Mock(
                returncode=0,
                stdout=json.dumps(VALID),
                stderr="",
            )

            result = fetch_and_store(
                destination=destination,
                runner=fake_runner,
            )

            self.assertEqual(
                result["schema_version"],
                1,
            )

            saved = json.loads(
                destination.read_text(
                    encoding="utf-8"
                )
            )

            self.assertEqual(
                saved,
                VALID,
            )

    def test_fetch_and_store_does_not_replace_cache_on_bad_json(self):
        with tempfile.TemporaryDirectory() as tmp:
            destination = Path(tmp) / "status.json"

            write_atomic(
                destination,
                VALID,
            )

            before = destination.read_text(
                encoding="utf-8"
            )

            fake_runner = Mock()

            fake_runner.return_value = Mock(
                returncode=0,
                stdout="{broken",
                stderr="",
            )

            with self.assertRaises(
                StatusValidationError
            ):
                fetch_and_store(
                    destination=destination,
                    runner=fake_runner,
                )

            after = destination.read_text(
                encoding="utf-8"
            )

            self.assertEqual(
                before,
                after,
            )

    def test_fetch_and_store_rejects_ssh_failure(self):
        with tempfile.TemporaryDirectory() as tmp:
            destination = Path(tmp) / "status.json"

            fake_runner = Mock()

            fake_runner.return_value = Mock(
                returncode=255,
                stdout="",
                stderr="connection failed",
            )

            with self.assertRaises(
                StatusValidationError
            ):
                fetch_and_store(
                    destination=destination,
                    runner=fake_runner,
                )

            self.assertFalse(
                destination.exists()
            )
