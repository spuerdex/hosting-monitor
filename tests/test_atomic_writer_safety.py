"""Atomic cache writer safety tests."""

import tempfile
import unittest

from pathlib import Path
from unittest.mock import patch

from ops.status_fetcher import write_atomic


class AtomicWriterSafetyTests(unittest.TestCase):

    def test_replace_failure_preserves_cache_and_cleans_temp(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            destination = root / "status.json"

            original = b"ORIGINAL_CACHE"

            destination.write_bytes(original)

            document = {
                "schema_version": 1,
                "overall_status": "HEALTHY",
            }

            # Simulate a filesystem replacement failure.
            with patch(
                "ops.status_fetcher.os.replace",
                side_effect=OSError(
                    "SIMULATED_REPLACE_FAILURE"
                ),
            ):
                with self.assertRaises(OSError):
                    write_atomic(
                        destination,
                        document,
                    )

            # The previously published cache must survive.
            self.assertEqual(
                destination.read_bytes(),
                original,
            )

            # Failed writes must leave no temporary files.
            self.assertEqual(
                list(root.iterdir()),
                [destination],
            )


if __name__ == "__main__":
    unittest.main()
