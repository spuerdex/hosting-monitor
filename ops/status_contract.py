"""Validation and normalization for host status API documents."""

import copy
import datetime as dt


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


class StatusContractError(ValueError):
    """A status document does not satisfy the shared schema."""


def _valid_timestamp(value):
    if not isinstance(value, str) or not value.strip():
        return False

    try:
        dt.datetime.fromisoformat(value.replace("Z", "+00:00"))
    except ValueError:
        return False

    return value.endswith("Z") or "+" in value or "-" in value[10:]


def _validate_student(student):
    if not isinstance(student, dict):
        raise StatusContractError("student row must be an object")

    student_id = student.get("student_id")

    if not isinstance(student_id, str) or not student_id.strip():
        raise StatusContractError("student_id must be a non-empty string")

    if "domain" in student and not isinstance(student["domain"], str):
        raise StatusContractError("domain must be a string")

    if "status" in student and not isinstance(student["status"], str):
        raise StatusContractError("student status must be a string")

    if "quota" in student and not isinstance(student["quota"], dict):
        raise StatusContractError("quota must be an object")


def validate_status(document: dict) -> dict:
    if not isinstance(document, dict):
        raise StatusContractError("status document must be an object")

    if document.get("schema_version") != 1:
        raise StatusContractError("unsupported status schema version")

    missing = REQUIRED_KEYS - document.keys()

    if missing:
        raise StatusContractError(
            "missing required keys: " + ", ".join(sorted(missing))
        )

    if not _valid_timestamp(document["generated_at"]):
        raise StatusContractError("invalid generated_at timestamp")

    if document["overall_status"] not in {"HEALTHY", "WARNING"}:
        raise StatusContractError("invalid overall_status")

    for key in ("services", "storage", "summary", "backup"):
        if not isinstance(document[key], dict):
            raise StatusContractError(f"{key} must be an object")

    if not isinstance(document["students"], list):
        raise StatusContractError("students must be a list")

    for student in document["students"]:
        _validate_student(student)

    return document


def normalize_status(document: dict, host: dict) -> dict:
    validate_status(document)

    host_code = host.get("code") if isinstance(host, dict) else None

    if not isinstance(host_code, str) or not host_code:
        raise StatusContractError("host code is required")

    normalized = copy.deepcopy(document)
    normalized["host_code"] = host_code
    return normalized
