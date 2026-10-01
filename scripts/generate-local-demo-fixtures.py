"""Generate deterministic local-only monitoring fixtures for five demo VMs."""

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
FIXTURE_ROOT = ROOT / "fixtures" / "hosts"

HOSTS = [
    {
        "code": "cs-01",
        "prefix": "CS",
        "root": 38,
        "student_disk": 42,
        "status": "HEALTHY",
        "enabled": 15,
        "suspended": 1,
        "warnings": 0,
        "mariadb": True,
        "php_fpm": True,
        "backup_mb": 128,
    },
    {
        "code": "cs-02",
        "prefix": "CS",
        "root": 52,
        "student_disk": 58,
        "status": "HEALTHY",
        "enabled": 16,
        "suspended": 0,
        "warnings": 0,
        "mariadb": True,
        "php_fpm": True,
        "backup_mb": 164,
    },
    {
        "code": "it-01",
        "prefix": "IT",
        "root": 76,
        "student_disk": 71,
        "status": "WARNING",
        "enabled": 15,
        "suspended": 1,
        "warnings": 2,
        "mariadb": False,
        "php_fpm": True,
        "backup_mb": 211,
    },
    {
        "code": "eng-01",
        "prefix": "ENG",
        "root": 64,
        "student_disk": 49,
        "status": "HEALTHY",
        "enabled": 14,
        "suspended": 2,
        "warnings": 1,
        "mariadb": True,
        "php_fpm": True,
        "backup_mb": 186,
    },
    {
        "code": "sci-01",
        "prefix": "SCI",
        "root": 83,
        "student_disk": 79,
        "status": "WARNING",
        "enabled": 14,
        "suspended": 2,
        "warnings": 3,
        "mariadb": True,
        "php_fpm": False,
        "backup_mb": 247,
    },
]


def make_students(host: dict) -> list[dict]:
    students = []
    suspended_start = host["enabled"]

    for index in range(16):
        student_number = index + 1
        state = "suspended" if index >= suspended_start else "enabled"
        used_mb = 240 + (index * 73) + (len(host["code"]) * 11)
        students.append(
            {
                "student_id": f"{host['prefix']}-{student_number:03d}",
                "username": f"{host['prefix'].lower()}{student_number:03d}",
                "domain": f"{host['code']}-{student_number:03d}.demo.local",
                "status": state,
                "php_pool": state == "enabled" and host["php_fpm"],
                "quota": {
                    "used_mb": used_mb,
                    "soft_mb": 1024,
                    "hard_mb": 2048,
                },
            }
        )

    return students


def make_fixture(host: dict) -> dict:
    students = make_students(host)
    return {
        "schema_version": 1,
        "generated_at": "2026-09-30T12:00:00Z",
        "overall_status": host["status"],
        "services": {
            "nginx": True,
            "php_fpm": host["php_fpm"],
            "mariadb": host["mariadb"],
            "ssh": True,
            "ufw": True,
        },
        "storage": {
            "root": {"used_percent": host["root"]},
            "student": {"used_percent": host["student_disk"]},
        },
        "summary": {
            "students": len(students),
            "enabled": host["enabled"],
            "suspended": host["suspended"],
            "warnings": host["warnings"],
        },
        "backup": {
            "last_backup": "2026-09-30T10:30:00Z",
            "size_bytes": host["backup_mb"] * 1024 * 1024,
        },
        "students": students,
    }


def main() -> None:
    for host in HOSTS:
        destination = FIXTURE_ROOT / host["code"]
        destination.mkdir(parents=True, exist_ok=True)
        path = destination / "status.json"
        path.write_text(
            json.dumps(make_fixture(host), indent=2, ensure_ascii=False) + "\n",
            encoding="utf-8",
        )

    print(f"Generated {len(HOSTS)} demo host fixtures with 80 students.")


if __name__ == "__main__":
    main()
