import json
import os
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

    os.replace(
        temp,
        destination,
    )
