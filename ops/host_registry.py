"""Load the DiGiT Student Hosting host registry."""

import ipaddress
import json
import re
from pathlib import Path


HOST_CODE_RE = re.compile(r"^[a-z][a-z0-9_-]{0,31}$")

RESERVED_CODES = {"status", "metadata"}

REQUIRED_HOST_FIELDS = {
    "code",
    "name",
    "ip",
    "ssh_user",
    "enabled",
}

class RegistryError(ValueError):
    """Invalid host registry configuration."""

def load_registry(path: Path) -> list[dict]:
    """Read and validate hosts from a JSON registry."""

    try:
        document = json.loads(
            Path(path).read_text(encoding="utf-8")
        )
    except json.JSONDecodeError:
        raise RegistryError(
            "malformed registry JSON"
        ) from None

    if not isinstance(document, dict):
        raise RegistryError(
            "registry must be a JSON object"
        )

    # Validate schema version and its actual type
    if (
        type(document.get("schema_version")) is not int
        or document["schema_version"] != 1
    ):
        raise RegistryError("unsupported registry schema version")

    if "hosts" not in document:
        raise RegistryError(
            "missing hosts field"
        )

    hosts = document["hosts"]

    # Hosts must be a list
    if not isinstance(hosts, list):
        raise RegistryError("hosts must be a list")

    seen_codes = set()

    for host in hosts:

        # Each host must be a dictionary
        if not isinstance(host, dict):
            raise RegistryError("invalid host entry")

        # Validate exact host fields
        if set(host.keys()) != REQUIRED_HOST_FIELDS:
            raise RegistryError("invalid host fields")

        code = host["code"]

        # Validate host code
        if (
            not isinstance(code, str)
            or not HOST_CODE_RE.fullmatch(code)
        ):
            raise RegistryError("invalid host code")

        if code in RESERVED_CODES:
            raise RegistryError("reserved host code")

        if code in seen_codes:
            raise RegistryError("duplicate host code")

        seen_codes.add(code)

        # Validate host name
        name = host["name"]

        if (
            not isinstance(name, str)
            or not name.strip()
        ):
            raise RegistryError("invalid host name")

        # Validate IPv4 literal
        ip = host["ip"]

        if not isinstance(ip, str):
            raise RegistryError("invalid host IP")

        try:
            ipaddress.IPv4Address(ip)
        except ValueError as exc:
            raise RegistryError("invalid host IP") from exc

        # Fixed SSH identity
        if host["ssh_user"] != "hostingportal":
            raise RegistryError("unauthorized SSH user")

        # Enabled must be a real boolean
        if not isinstance(host["enabled"], bool):
            raise RegistryError("invalid enabled value")

    return hosts
