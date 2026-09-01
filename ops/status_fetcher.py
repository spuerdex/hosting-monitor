import json
import os
import subprocess
from pathlib import Path


class StatusValidationError(ValueError):
    pass


REQUIRED_KEYS = {
    "schema_version",
    "generated_at",
    "overall_status",
    "services",
    "storage",
    "summary",
    "backup",
    "students",
}


SSH_COMMAND = [
    "/usr/bin/ssh",
    "-T",
    "-i",
    "/etc/digit-hosting-admin/ssh/id_ed25519",
    "-o",
    "BatchMode=yes",
    "-o",
    "IdentitiesOnly=yes",
    "-o",
    "ConnectTimeout=5",
    "-o",
    "StrictHostKeyChecking=yes",
    "-o",
    "UserKnownHostsFile=/etc/digit-hosting-admin/ssh/known_hosts",
    "hostingportal@10.1.161.23",
]


def validate_status(doc: dict) -> dict:
    if not isinstance(doc, dict):
        raise StatusValidationError(
            "status document must be an object"
        )

    if doc.get("schema_version") != 1:
        raise StatusValidationError(
            "unsupported schema version"
        )

    missing = REQUIRED_KEYS - doc.keys()

    if missing:
        raise StatusValidationError(
            "missing required keys: "
            + ", ".join(sorted(missing))
        )

    if not isinstance(doc["students"], list):
        raise StatusValidationError(
            "students must be a list"
        )

    return doc


def write_atomic(
    destination: Path,
    doc: dict,
) -> None:
    destination.parent.mkdir(
        parents=True,
        exist_ok=True,
    )

    temp = destination.with_name(
        destination.name + ".tmp"
    )

    payload = (
        json.dumps(
            doc,
            ensure_ascii=False,
            separators=(",", ":"),
        )
        + "\n"
    )

    temp.write_text(
        payload,
        encoding="utf-8",
    )

    os.chmod(
        temp,
        0o640,
    )

    os.replace(
        temp,
        destination,
    )


def fetch_and_store(
    destination: Path,
    runner=subprocess.run,
) -> dict:
    result = runner(
        SSH_COMMAND,
        text=True,
        capture_output=True,
        check=False,
        timeout=10,
    )

    if result.returncode != 0:
        raise StatusValidationError(
            "SSH status request failed"
        )

    try:
        doc = json.loads(
            result.stdout
        )
    except json.JSONDecodeError as exc:
        raise StatusValidationError(
            "invalid JSON returned by hosting server"
        ) from exc

    validated = validate_status(doc)

    write_atomic(
        destination,
        validated,
    )

    return validated
