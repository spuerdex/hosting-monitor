"""Bounded SSH output reader."""
import ipaddress
import json
import subprocess
import os
import select
import time

from ops.status_fetcher import validate_status
MAX_STATUS_BYTES = 1024 * 1024
CHUNK_SIZE = 64 * 1024


def read_limited(stream, limit=MAX_STATUS_BYTES) -> bytes:
    """Read binary output without exceeding the size limit."""

    if type(limit) is not int or limit < 0:
        raise ValueError("invalid payload size limit")

    chunks = []
    total = 0

    while True:
        # Read at most one byte beyond the allowed size
        # to detect oversized output.
        remaining = limit + 1 - total
        read_size = min(CHUNK_SIZE, remaining)

        chunk = stream.read(read_size)

        if not isinstance(chunk, bytes):
            raise ValueError("invalid binary stream")

        if len(chunk) > read_size:
            raise ValueError("stream exceeded requested read size")

        if not chunk:
            break

        total += len(chunk)

        if total > limit:
            raise ValueError("status payload too large")

        chunks.append(chunk)

    return b"".join(chunks)

def fetch_status(host: dict, *, process_factory) -> dict:
    """Fetch status using an injected SSH process factory."""

    # Validate the target before constructing SSH arguments.
    if not isinstance(host, dict):
        raise ValueError("invalid host")

    if host.get("ssh_user") != "hostingportal":
        raise ValueError("invalid SSH user")

    ip = host.get("ip")

    if not isinstance(ip, str):
        raise ValueError("invalid host IP")

    try:
        address = ipaddress.IPv4Address(ip)
    except ValueError:
        raise ValueError("invalid host IP") from None

    if str(address) != ip:
        raise ValueError("invalid host IP")

    argv = [
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
        f"hostingportal@{ip}",
    ]

    process = process_factory(
        argv,
        shell=False,
        stdin=subprocess.DEVNULL,
        stdout=subprocess.PIPE,
        stderr=subprocess.DEVNULL,
    )

        # One deadline for reading and waiting.
    deadline = time.monotonic() + 20.0

    try:
        payload = read_limited_deadline(
            process.stdout,
            limit=MAX_STATUS_BYTES,
            deadline=deadline,
        )

        remaining = deadline - time.monotonic()

        if remaining <= 0:
            raise TimeoutError(
                "SSH status operation timed out"
            )

        returncode = process.wait(
            timeout=remaining,
        )

    except Exception as exc:
        # Terminate and reap the child on read/wait failure.
        try:
            process.kill()
        except ProcessLookupError:
            # The process may have exited already.
            pass

        try:
            process.wait(timeout=5)
        except subprocess.TimeoutExpired:
            # Cleanup did not complete; do not claim success.
            raise RuntimeError(
                "SSH process cleanup timed out"
            ) from None

        if isinstance(exc, subprocess.TimeoutExpired):
            raise TimeoutError(
                "SSH status operation timed out"
            ) from None

        raise

    finally:
        # Always close the stdout stream.
        if process.stdout is not None:
            process.stdout.close()

    if returncode != 0:
        raise ValueError("SSH status fetch failed")

    try:
        document = json.loads(payload)
    except (ValueError, UnicodeDecodeError):
        raise ValueError("invalid status JSON") from None

    return validate_status(document)
def read_limited_deadline(
    stream,
    *,
    limit=MAX_STATUS_BYTES,
    deadline,
    clock=None,
    wait_ready=None,
    read_chunk=None,
) -> bytes:
    """Read bounded output with a monotonic deadline."""

    if type(limit) is not int or limit < 0:
        raise ValueError("invalid payload size limit")

    if clock is None:
        clock = time.monotonic

    if wait_ready is None:
        def wait_ready(target, timeout):
            readable, _, _ = select.select(
                [target],
                [],
                [],
                timeout,
            )
            return bool(readable)

    if read_chunk is None:
        def read_chunk(target, size):
            return os.read(target.fileno(), size)

    chunks = []
    total = 0

    while True:
        remaining_time = deadline - clock()

        if remaining_time <= 0:
            raise TimeoutError("SSH status read timed out")

        # Never read before the descriptor is ready.
        if not wait_ready(stream, remaining_time):
            continue

        remaining_bytes = limit + 1 - total
        read_size = min(CHUNK_SIZE, remaining_bytes)

        chunk = read_chunk(stream, read_size)

        if not isinstance(chunk, bytes):
            raise ValueError("invalid binary stream")

        if len(chunk) > read_size:
            raise ValueError("stream exceeded read size")

        # EOF
        if not chunk:
            return b"".join(chunks)

        total += len(chunk)

        if total > limit:
            raise ValueError("status payload too large")

        chunks.append(chunk)
