import json
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class DemoFixtureTests(unittest.TestCase):
    def test_local_demo_registry_has_five_hosts(self):
        registry = json.loads(
            (ROOT / "config/hosts.local.example.json").read_text()
        )

        self.assertEqual(registry["schema_version"], 2)
        self.assertEqual(len(registry["hosts"]), 5)
        self.assertEqual(
            {host["code"] for host in registry["hosts"]},
            {"cs-01", "cs-02", "it-01", "eng-01", "sci-01"},
        )

    def test_local_demo_fixtures_contain_eighty_students(self):
        registry = json.loads(
            (ROOT / "config/hosts.local.example.json").read_text()
        )
        counts = {}

        for host in registry["hosts"]:
            fixture = json.loads(
                (ROOT / "fixtures/hosts" / host["code"] / "status.json")
                .read_text()
            )
            students = fixture["students"]
            counts[host["code"]] = len(students)
            self.assertEqual(fixture["summary"]["students"], len(students))
            self.assertEqual(
                fixture["summary"]["enabled"]
                + fixture["summary"]["suspended"],
                len(students),
            )
            self.assertTrue(all(student.get("domain") for student in students))
            self.assertTrue(all(student.get("quota") for student in students))

        self.assertEqual(counts, {code: 16 for code in counts})
        self.assertEqual(sum(counts.values()), 80)


if __name__ == "__main__":
    unittest.main()
