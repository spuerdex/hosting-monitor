"""Multi-host status orchestration."""
import json
from datetime import datetime
from pathlib import Path
from typing import Callable

from ops.host_registry import load_registry
from ops.status_fetcher import validate_status, write_atomic
MAX_STATUS_BYTES = 1024 * 1024

def fetch_all(
    registry_path: Path,
    cache_dir: Path,
    fetch: Callable[[dict], dict],
    now: Callable[[], datetime],
) -> dict[str, str]:
    """Fetch enabled hosts using an injected adapter."""

    # Validate the entire registry before any writes.
    hosts = load_registry(registry_path)

    cache_dir = Path(cache_dir)
    metadata_dir = cache_dir / "metadata"

    results = {}

    for host in hosts:
        if not host["enabled"]:
            continue

        code = host["code"]

        # The caller injects the fetch adapter.
        # No real SSH occurs in this function.
        try:
            document = fetch(host)

            # Reject oversized serialized JSON before
            # validating or publishing the cache.
            payload_bytes = len(
                json.dumps(
                    document,
                    ensure_ascii=False,
                    allow_nan=False,
                ).encode("utf-8")
            )

            if payload_bytes > MAX_STATUS_BYTES:
                raise ValueError("status payload too large")

            validated = validate_status(document)

            # Reject unknown status before publishing cache.
            if validated.get("overall_status") not in (
                "HEALTHY",
                "WARNING",
            ):
                raise ValueError("invalid overall status")

        except Exception:
            # Do not expose raw exception details.
            metadata_dir.mkdir(
                parents=True,
                exist_ok=True,
            )

                       # Preserve last successful fetch timestamp.
            metadata_path = metadata_dir / f"{code}.json"
            previous_success = None

            try:
                previous_metadata = json.loads(
                    metadata_path.read_text(encoding="utf-8")
                )
            except (
                FileNotFoundError,
                json.JSONDecodeError,
                UnicodeDecodeError,
            ):
                previous_metadata = None

            if (
                isinstance(previous_metadata, dict)
                and previous_metadata.get("host_code") == code
                and isinstance(
                    previous_metadata.get("last_success"),
                    str,
                )
            ):
                previous_success = previous_metadata["last_success"]

            failure_metadata = {
                "host_code": code,
                "last_attempt": now().isoformat(),
                "last_success": previous_success,
                "fetch_state": "UNREACHABLE",
                "error_category": "FETCH_FAILED",
            }

            write_atomic(
                metadata_dir / f"{code}.json",
                failure_metadata,
            )

            results[code] = "UNREACHABLE"

            # Continue with the remaining hosts.
            continue

        cache_dir.mkdir(
            parents=True,
            exist_ok=True,
        )

        metadata_dir.mkdir(
            parents=True,
            exist_ok=True,
        )

        timestamp = now().isoformat()

        # Preserve the previous successful publication time.
        metadata_path = metadata_dir / f"{code}.json"

        try:
            previous_metadata = json.loads(
                metadata_path.read_text(encoding="utf-8")
            )
        except (
            FileNotFoundError,
            json.JSONDecodeError,
            UnicodeDecodeError,
        ):
            previous_metadata = None

        previous_success = None

        if (
            isinstance(previous_metadata, dict)
            and previous_metadata.get("host_code") == code
            and isinstance(
                previous_metadata.get("last_success"),
                str,
            )
        ):
            previous_success = previous_metadata["last_success"]

        # Publication marker must be written before cache files.
        write_atomic(
            metadata_path,
            {
                "host_code": code,
                "last_attempt": timestamp,
                "last_success": previous_success,
                "fetch_state": "PUBLISHING",
                "error_category": None,
            },
        )

        # Independent cache for this host.
        write_atomic(
            cache_dir / f"{code}.json",
            validated,
        )

        # Compatibility cache for CS only.
        if code == "cs":
            write_atomic(
                cache_dir / "status.json",
                validated,
            )

        metadata = {
            "host_code": code,
            "last_attempt": timestamp,
            "last_success": timestamp,
            "fetch_state": "SUCCESS",
            "error_category": None,
        }

        write_atomic(
            metadata_dir / f"{code}.json",
            metadata,
        )

        results[code] = validated["overall_status"]

    return results
