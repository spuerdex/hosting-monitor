import copy
import unittest


VALID = {
    "schema_version": 1,
    "generated_at": "2026-09-30T12:00:00Z",
    "overall_status": "HEALTHY",
    "services": {"nginx": True},
    "storage": {"root": {"used_percent": 10}},
    "summary": {"students": 1},
    "backup": {"last_backup": None, "size_bytes": None},
    "students": [
        {
            "student_id": "s001",
            "domain": "s001.example.test",
            "status": "enabled",
        }
    ],
}


class StatusContractTests(unittest.TestCase):
    def test_validate_status_accepts_schema_v1(self):
        from ops.status_contract import validate_status

        self.assertEqual(validate_status(VALID), VALID)

    def test_validate_status_rejects_invalid_generated_at(self):
        from ops.status_contract import StatusContractError, validate_status

        invalid = copy.deepcopy(VALID)
        invalid["generated_at"] = "not-a-timestamp"

        with self.assertRaises(StatusContractError):
            validate_status(invalid)

    def test_validate_status_rejects_malformed_student_row(self):
        from ops.status_contract import StatusContractError, validate_status

        invalid = copy.deepcopy(VALID)
        invalid["students"] = [{"domain": "missing-id"}]

        with self.assertRaises(StatusContractError):
            validate_status(invalid)

    def test_normalize_status_adds_host_scope(self):
        from ops.status_contract import normalize_status

        normalized = normalize_status(
            VALID,
            {"code": "cs"},
        )

        self.assertEqual(normalized["host_code"], "cs")
        self.assertEqual(normalized["students"][0]["student_id"], "s001")


if __name__ == "__main__":
    unittest.main()
