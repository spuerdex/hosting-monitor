"""Multi-host CLI exit-code handling."""

import argparse

from pathlib import Path

from ops.host_registry import RegistryError


def cli_main(argv, *, execute) -> int:
    """Run an injected fetch operation and return its exit code."""

    parser = argparse.ArgumentParser(
        description="DiGiT Multi-Host Status Fetcher"
    )

    parser.add_argument(
        "--registry",
        type=Path,
        required=True,
    )

    parser.add_argument(
        "--cache-dir",
        type=Path,
        required=True,
    )

    args = parser.parse_args(argv)

    try:
        results = execute(
            registry_path=args.registry,
            cache_dir=args.cache_dir,
        )

    except RegistryError:
        # Invalid registry configuration.
        return 2

    except Exception:
        # Do not expose internal errors or sensitive details.
        return 1

    # No enabled hosts.
    if not results:
        return 2

    # Only recognized successful statuses count.
    if any(
        status in ("HEALTHY", "WARNING")
        for status in results.values()
    ):
        return 0

    # Every enabled host failed.
    return 1
