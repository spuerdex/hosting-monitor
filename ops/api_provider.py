"""Read-only HTTP provider for one configured hosting host."""

import json
import os
from typing import Callable
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import (
    HTTPRedirectHandler,
    Request,
    build_opener,
)


MAX_STATUS_BYTES = 1024 * 1024


class ApiProviderError(RuntimeError):
    """A host API could not provide a trusted status document."""


def _default_transport(
    url: str,
    headers: dict[str, str],
    timeout: int,
    max_bytes: int,
) -> tuple[int, bytes]:
    request = Request(url, headers=headers, method="GET")

    try:
        with build_opener(_NoRedirectHandler()).open(
            request,
            timeout=timeout,
        ) as response:
            payload = response.read(max_bytes + 1)
            return response.status, payload
    except HTTPError as exc:
        raise ApiProviderError(
            f"host API returned HTTP {exc.code}"
        ) from None
    except (URLError, TimeoutError, OSError) as exc:
        raise ApiProviderError(
            "host API request failed"
        ) from exc


class _NoRedirectHandler(HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, new_url):
        return None


def fetch_status(
    host: dict,
    *,
    transport: Callable | None = None,
) -> dict:
    """Fetch one host's JSON status document without mutating the host."""

    if not isinstance(host, dict):
        raise ApiProviderError("invalid host configuration")

    base_url = host.get("base_url")
    timeout = host.get("timeout_seconds")
    token = host.get("api_token")

    if token is None and isinstance(host.get("api_token_env"), str):
        token = os.environ.get(host["api_token_env"])

    parsed = urlparse(base_url) if isinstance(base_url, str) else None

    if (
        parsed is None
        or parsed.scheme not in {"http", "https"}
        or not parsed.netloc
        or parsed.query
        or parsed.fragment
    ):
        raise ApiProviderError("invalid host API URL")

    if type(timeout) is not int or not 1 <= timeout <= 60:
        raise ApiProviderError("invalid host API timeout")

    if not isinstance(token, str) or token == "":
        raise ApiProviderError("missing host API token")

    url = base_url.rstrip("/") + "/status"
    headers = {
        "Accept": "application/json",
        "Authorization": f"Bearer {token}",
    }
    request_transport = transport or _default_transport

    try:
        status, payload = request_transport(
            url,
            headers,
            timeout,
            MAX_STATUS_BYTES,
        )
    except ApiProviderError:
        raise
    except Exception as exc:
        raise ApiProviderError("host API request failed") from exc

    if status != 200:
        raise ApiProviderError(
            f"host API returned HTTP {status}"
        )

    if not isinstance(payload, bytes):
        raise ApiProviderError("host API returned invalid body")

    if len(payload) > MAX_STATUS_BYTES:
        raise ApiProviderError("status payload too large")

    try:
        document = json.loads(payload)
    except (UnicodeDecodeError, json.JSONDecodeError) as exc:
        raise ApiProviderError("host API returned invalid JSON") from exc

    if not isinstance(document, dict):
        raise ApiProviderError("host API returned non-object JSON")

    return document
