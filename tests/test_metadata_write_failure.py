"""Metadata publication failure tests."""

import copy
import io
import json
import tempfile
import unittest

from contextlib import redirect_stdout, redirect_stderr
from datetime import datetime, timezone
from pathlib import Path
from unittest.mock import patch

from ops.status_fetcher import write_atomic


class MetadataWriteFailureTests(unittest.TestCase):

    def test_metadata_write_failures(self):

        from ops.multi_host_entrypoint import main

        for failure_stage in ("PUBLISHING", "SUCCESS"):

            with self.subTest(stage=failure_stage):

                with tempfile.TemporaryDirectory() as tmp:

                    root = Path(tmp)
                    registry_path = root / "hosts.json"
                    cache_dir = root / "cache"
                    metadata_dir = cache_dir / "metadata"

                    metadata_dir.mkdir(parents=True)

                    registry = json.loads(
                        Path("config/hosts.example.json").read_text(
                            encoding="utf-8"
                        )
                    )

                    for host in registry["hosts"]:
                        host["enabled"] = host["code"] == "cs"

                    registry_path.write_text(
                        json.dumps(registry),
                        encoding="utf-8",
                    )

                    cs_path = cache_dir / "cs.json"
                    legacy_path = cache_dir / "status.json"
                    metadata_path = metadata_dir / "cs.json"

                    old_cache = b"OLD_VALID_CACHE"

                    cs_path.write_bytes(old_cache)
                    legacy_path.write_bytes(old_cache)

                    old_success = "2026-09-17T02:00:00+00:00"

                    write_atomic(
                        metadata_path,
                        {
                            "host_code": "cs",
                            "last_attempt": old_success,
                            "last_success": old_success,
                            "fetch_state": "SUCCESS",
                            "error_category": None,
                        },
                    )

                    payload = {
                        "schema_version": 1,
                        "generated_at":
                            "2026-09-18T09:00:00+07:00",
                        "overall_status": "HEALTHY",
                        "services": {},
                        "storage": {},
                        "summary": {},
                        "backup": {},
                        "students": [],
                    }

                    writes = []
                    fetch_calls = []

                    def forbidden_process(*args, **kwargs):
                        raise AssertionError(
                            "Real process must not start"
                        )

                    def fake_fetch(host, *, process_factory):
                        self.assertIs(
                            process_factory,
                            forbidden_process,
                        )
                        fetch_calls.append(host["code"])
                        return copy.deepcopy(payload)

                    def fake_write(destination, document):

                        destination = Path(destination)

                        state = document.get("fetch_state")

                        writes.append(
                            (destination, state)
                        )

                        if (
                            destination == metadata_path
                            and state == failure_stage
                        ):
                            raise OSError(
                                "PRIVATE_METADATA_FAILURE"
                            )

                        return write_atomic(
                            destination,
                            document,
                        )

                    stdout = io.StringIO()
                    stderr = io.StringIO()

                    with patch(
                        "ops.ssh_adapter.fetch_status",
                        side_effect=fake_fetch,
                    ), patch(
                        "ops.multi_host_fetcher.write_atomic",
                        side_effect=fake_write,
                    ), redirect_stdout(stdout), redirect_stderr(stderr):

                        exit_code = main(
                            [
                                "--registry",
                                str(registry_path),
                                "--cache-dir",
                                str(cache_dir),
                            ],
                            process_factory=forbidden_process,
                            now=lambda: datetime(
                                2026, 9, 18, 2, 0,
                                tzinfo=timezone.utc,
                            ),
                        )

                    self.assertEqual(exit_code, 1)
                    self.assertEqual(fetch_calls, ["cs"])

                    self.assertNotIn(
                        "PRIVATE_METADATA_FAILURE",
                        stdout.getvalue() + stderr.getvalue(),
                    )

                    metadata = json.loads(
                        metadata_path.read_text(
                            encoding="utf-8"
                        )
                    )

                    self.assertEqual(
                        metadata["last_success"],
                        old_success,
                    )

                    if failure_stage == "PUBLISHING":

                        self.assertEqual(
                            metadata["fetch_state"],
                            "SUCCESS",
                        )

                        self.assertEqual(
                            cs_path.read_bytes(),
                            old_cache,
                        )

                        self.assertEqual(
                            legacy_path.read_bytes(),
                            old_cache,
                        )

                        self.assertEqual(
                            len(writes),
                            1,
                        )

                    else:

                        self.assertEqual(
                            metadata["fetch_state"],
                            "PUBLISHING",
                        )

                        self.assertEqual(
                            json.loads(
                                cs_path.read_text(
                                    encoding="utf-8"
                                )
                            ),
                            payload,
                        )

                        self.assertEqual(
                            json.loads(
                                legacy_path.read_text(
                                    encoding="utf-8"
                                )
                            ),
                            payload,
                        )

                        self.assertEqual(
                            writes[-1],
                            (metadata_path, "SUCCESS"),
                        )


if __name__ == "__main__":
    unittest.main()
