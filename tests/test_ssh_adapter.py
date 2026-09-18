import io
import unittest
import json
import subprocess
import tempfile
MAX_BYTES = 1024 * 1024


class TrackingStream(io.BytesIO):
    """Track how many bytes the reader consumes."""

    def __init__(self, data):
        super().__init__(data)
        self.total_read = 0

    def read(self, size=-1):
        if size < 0:
            raise AssertionError("unbounded read forbidden")

        chunk = super().read(size)
        self.total_read += len(chunk)

        if self.total_read > MAX_BYTES + 1:
            raise AssertionError("read exceeded limit")

        return chunk


class SSHAdapterTests(unittest.TestCase):

    def test_reject_oversized_stream(self):
        from ops.ssh_adapter import read_limited

        stream = TrackingStream(
            b"X" * (MAX_BYTES + 2)
        )

        with self.assertRaises(ValueError):
            read_limited(
                stream,
                limit=MAX_BYTES,
            )

        self.assertLessEqual(
            stream.total_read,
            MAX_BYTES + 1,
        )


    def test_accept_exact_limit(self):
        from ops.ssh_adapter import read_limited

        payload = b"A" * MAX_BYTES
        stream = TrackingStream(payload)

        result = read_limited(
            stream,
            limit=MAX_BYTES,
        )

        self.assertEqual(result, payload)
        self.assertEqual(stream.total_read, MAX_BYTES)

    def test_reads_multiple_chunks(self):
        from ops.ssh_adapter import read_limited

        payload = b"B" * 150000

        class ChunkTrackingStream(TrackingStream):
            def __init__(self, data):
                super().__init__(data)
                self.requests = []

            def read(self, size=-1):
                self.requests.append(size)
                return super().read(size)

        stream = ChunkTrackingStream(payload)

        result = read_limited(
            stream,
            limit=MAX_BYTES,
        )

        self.assertEqual(result, payload)
        self.assertGreater(len(stream.requests), 2)

        self.assertTrue(
            all(
                0 < size <= 65536
                for size in stream.requests
            )
        )
    def test_fetch_status_uses_safe_ssh_command(self):
        from ops.ssh_adapter import fetch_status

        host = {
            "code": "cs",
            "name": "Computer Science",
            "ip": "192.0.2.11",
            "ssh_user": "hostingportal",
            "enabled": True,
        }

        payload = {
            "schema_version": 1,
            "generated_at": "2026-09-18T09:00:00+07:00",
            "overall_status": "HEALTHY",
            "services": {},
            "storage": {},
            "summary": {},
            "backup": {},
            "students": [],
        }

        calls = []

        class FakeProcess:
            def __init__(self):
                self.stdout = tempfile.TemporaryFile(mode="w+b")
                self.stdout.write(
                     json.dumps(payload).encode("utf-8")
                )
                self.stdout.seek(0)
                self.returncode = 0

            def wait(self, timeout=None):
                return self.returncode

        def fake_popen(argv, **kwargs):
            calls.append((argv, kwargs))
            return FakeProcess()

        result = fetch_status(
            host,
            process_factory=fake_popen,
        )

        self.assertEqual(result, payload)
        self.assertEqual(len(calls), 1)

        argv, kwargs = calls[0]

        self.assertIsInstance(argv, list)

        self.assertEqual(argv[0], "/usr/bin/ssh")
        self.assertEqual(argv[-1], "hostingportal@192.0.2.11")

        self.assertIn("-T", argv)
        self.assertIn("BatchMode=yes", argv)
        self.assertIn("IdentitiesOnly=yes", argv)
        self.assertIn("StrictHostKeyChecking=yes", argv)

        self.assertIn(
            "UserKnownHostsFile=/etc/digit-hosting-admin/ssh/known_hosts",
            argv,
        )

        self.assertIs(kwargs.get("shell"), False)

        self.assertEqual(
            kwargs.get("stdout"),
            subprocess.PIPE,
        )

        self.assertEqual(
            kwargs.get("stderr"),
            subprocess.DEVNULL,
        )

    def test_oversized_output_kills_and_reaps_process(self):
        from ops.ssh_adapter import fetch_status

        host = {
            "code": "cs",
            "name": "Computer Science",
            "ip": "192.0.2.11",
            "ssh_user": "hostingportal",
            "enabled": True,
        }

        class FakeProcess:
            def __init__(self):
                self.stdout = tempfile.TemporaryFile(mode="w+b")
                self.stdout.write(b"X" * (MAX_BYTES + 2))
                self.stdout.seek(0)
                self.returncode = None
                self.kill_calls = 0
                self.wait_calls = 0

            def kill(self):
                self.kill_calls += 1
                self.returncode = -9

            def wait(self, timeout=None):
                self.wait_calls += 1
                return self.returncode

        process = FakeProcess()

        def fake_popen(argv, **kwargs):
            return process

        with self.assertRaisesRegex(
            ValueError, "status payload too large"
        ):
            fetch_status(
                host,
                process_factory=fake_popen,
            )

        # Process must be terminated and reaped.
        self.assertEqual(process.kill_calls, 1)
        self.assertGreaterEqual(process.wait_calls, 1)

        # Close stdout even when reading fails.
        self.assertTrue(process.stdout.closed)


    def test_stream_deadline_expires_without_reading(self):
        from ops.ssh_adapter import read_limited_deadline

        class NeverReadyStream:
            def read(self, size=-1):
                raise AssertionError(
                    "read must not be called before readiness"
                )

        stream = NeverReadyStream()

        state = {"time": 100.0}
        waits = []

        def fake_clock():
            return state["time"]

        def fake_wait_ready(target, timeout):
            self.assertIs(target, stream)
            self.assertGreater(timeout, 0)

            waits.append(timeout)

            # Prevent a broken implementation from looping forever.
            if len(waits) > 4:
                raise AssertionError("deadline was not enforced")

            state["time"] += min(timeout, 0.4)

            return False

        with self.assertRaises(TimeoutError):
            read_limited_deadline(
                stream,
                limit=MAX_BYTES,
                deadline=101.0,
                clock=fake_clock,
                wait_ready=fake_wait_ready,
            )

        self.assertGreaterEqual(len(waits), 2)

        self.assertLessEqual(
            state["time"],
            101.0,
        )



    def test_deadline_reader_handles_chunks_and_eof(self):
        from ops.ssh_adapter import read_limited_deadline

        payload = b"A" * 65536 + b"B" * 123
        position = 0
        sizes = []

        def fake_read_chunk(stream, size):
            nonlocal position

            sizes.append(size)

            chunk = payload[position:position + size]
            position += len(chunk)

            return chunk

        result = read_limited_deadline(
            object(),
            limit=MAX_BYTES,
            deadline=120.0,
            clock=lambda: 100.0,
            wait_ready=lambda stream, timeout: True,
            read_chunk=fake_read_chunk,
        )

        self.assertEqual(result, payload)
        self.assertEqual(position, len(payload))
        self.assertGreaterEqual(len(sizes), 3)
        self.assertTrue(
            all(0 < size <= 65536 for size in sizes)
        )

    def test_deadline_expires_after_partial_output(self):
        from ops.ssh_adapter import read_limited_deadline

        state = {
            "time": 10.0,
            "waits": 0,
            "reads": 0,
        }

        def fake_clock():
            return state["time"]

        def fake_wait_ready(stream, timeout):
            state["waits"] += 1

            if state["waits"] == 1:
                return True

            state["time"] = 11.0
            return False

        def fake_read_chunk(stream, size):
            state["reads"] += 1
            return b"partial"

        with self.assertRaises(TimeoutError):
            read_limited_deadline(
                object(),
                limit=MAX_BYTES,
                deadline=11.0,
                clock=fake_clock,
                wait_ready=fake_wait_ready,
                read_chunk=fake_read_chunk,
            )

        self.assertEqual(state["reads"], 1)
        self.assertEqual(state["waits"], 2)



    def test_fetch_status_uses_deadline_reader(self):
        from unittest.mock import patch
        from ops.ssh_adapter import fetch_status

        host = {
            "code": "cs",
            "name": "Computer Science",
            "ip": "192.0.2.11",
            "ssh_user": "hostingportal",
            "enabled": True,
        }

        payload = {
            "schema_version": 1,
            "generated_at": "2026-09-18T09:00:00+07:00",
            "overall_status": "HEALTHY",
            "services": {},
            "storage": {},
            "summary": {},
            "backup": {},
            "students": [],
        }

        class FakeProcess:
            def __init__(self):
                self.stdout = io.BytesIO(
                    json.dumps(payload).encode("utf-8")
                )
                self.returncode = 0
                self.kill_calls = 0
                self.wait_calls = 0

            def kill(self):
                self.kill_calls += 1
                self.returncode = -9

            def wait(self, timeout=None):
                self.wait_calls += 1
                return self.returncode

        process = FakeProcess()

        with patch(
            "ops.ssh_adapter.read_limited_deadline",
            side_effect=TimeoutError("simulated deadline"),
        ) as deadline_reader:

            with self.assertRaises(TimeoutError):
                fetch_status(
                    host,
                    process_factory=lambda *args, **kwargs: process,
                )

        deadline_reader.assert_called_once()

        self.assertEqual(process.kill_calls, 1)
        self.assertGreaterEqual(process.wait_calls, 1)
        self.assertTrue(process.stdout.closed)



    def test_process_wait_timeout_cleans_up(self):
        from unittest.mock import patch
        from ops.ssh_adapter import fetch_status

        host = {
            "code": "cs",
            "name": "Computer Science",
            "ip": "192.0.2.11",
            "ssh_user": "hostingportal",
            "enabled": True,
        }

        class FakeProcess:
            def __init__(self):
                self.stdout = io.BytesIO(b"{}")
                self.returncode = None
                self.kill_calls = 0
                self.wait_calls = []

            def kill(self):
                self.kill_calls += 1
                self.returncode = -9

            def wait(self, timeout=None):
                self.wait_calls.append(timeout)

                if len(self.wait_calls) == 1:
                    raise subprocess.TimeoutExpired(
                        cmd="ssh",
                        timeout=timeout,
                    )

                return self.returncode

        process = FakeProcess()

        def fake_popen(argv, **kwargs):
            return process

        with patch(
            "ops.ssh_adapter.read_limited_deadline",
            return_value=b"{}",
        ) as reader, patch(
            "ops.ssh_adapter.time.monotonic",
            side_effect=[100.0, 101.0],
        ):
            with self.assertRaisesRegex(
                TimeoutError,
                "SSH status operation timed out",
            ):
                fetch_status(
                    host,
                    process_factory=fake_popen,
                )

        reader.assert_called_once()

        self.assertEqual(process.kill_calls, 1)
        self.assertEqual(len(process.wait_calls), 2)

        self.assertAlmostEqual(
            process.wait_calls[0],
            19.0,
        )

        self.assertTrue(process.stdout.closed)


if __name__ == "__main__":
    unittest.main()
