"""Load the DiGiT Student Hosting host registry."""

import ipaddress
import json
import re
from pathlib import Path
from urllib.parse import urlparse


HOST_CODE_RE = re.compile(r"^[a-z][a-z0-9_-]{0,31}$")

RESERVED_CODES = {"status", "metadata"}

LEGACY_HOST_FIELDS = {
    "code",
    "name",
    "ip",
    "ssh_user",
    "enabled",
}

API_HOST_FIELDS = {
    "code",
    "name",
    "base_url",
    "enabled",
    "timeout_seconds",
    "api_token_env",
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
    schema_version = document.get("schema_version")

    if type(schema_version) is not int or schema_version not in (1, 2):
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

        expected_fields = (
            LEGACY_HOST_FIELDS
            if schema_version == 1
            else API_HOST_FIELDS
        )

        # Validate exact host fields
        if set(host.keys()) != expected_fields:
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

        if schema_version == 1:
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
        else:
            base_url = host["base_url"]
            parsed = urlparse(base_url) if isinstance(
                base_url,
                str,
            ) else None

            if (
                not isinstance(base_url, str)
                or parsed.scheme not in {"http", "https"}
                or not parsed.netloc
                or parsed.query
                or parsed.fragment
                or type(host["timeout_seconds"]) is not int
                or not 1 <= host["timeout_seconds"] <= 60
                or not isinstance(host["api_token_env"], str)
                or re.fullmatch(
                    r"[A-Z][A-Z0-9_]*",
                    host["api_token_env"],
                ) is None
            ):
                raise RegistryError("invalid API host configuration")

        # Enabled must be a real boolean
        if not isinstance(host["enabled"], bool):
            raise RegistryError("invalid enabled value")

    return hosts
