"""Multi-host integration entry point."""

import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path
from typing import Callable

from ops import api_provider
from ops import ssh_adapter
from ops.multi_host_fetcher import fetch_all


def run(
    registry_path: Path,
    cache_dir: Path,
    process_factory,
    now: Callable[[], datetime],
) -> dict[str, str]:
    """Connect the multi-host orchestrator to API or legacy SSH adapters."""

    def fetch(host: dict) -> dict:
        if "base_url" in host:
            return api_provider.fetch_status(host)

        return ssh_adapter.fetch_status(
            host,
            process_factory=process_factory,
        )

    return fetch_all(
        registry_path=registry_path,
        cache_dir=cache_dir,
        fetch=fetch,
        now=now,
    )


def main(
    argv,
    *,
    process_factory,
    now,
) -> int:
    """Connect CLI exit codes to the multi-host runner."""

    from ops.multi_host_cli import cli_main

    def execute(registry_path, cache_dir):
        return run(
            registry_path=registry_path,
            cache_dir=cache_dir,
            process_factory=process_factory,
            now=now,
        )

    return cli_main(
        argv,
        execute=execute,
    )


if __name__ == "__main__":
    raise SystemExit(
        main(
            sys.argv[1:],
            process_factory=subprocess.Popen,
            now=lambda: datetime.now(timezone.utc),
        )
    )
