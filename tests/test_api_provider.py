import unittest


class ApiProviderTests(unittest.TestCase):
    def test_fetch_status_reads_real_local_mock_api(self):
        from ops.api_provider import fetch_status
        from ops.mock_api_server import MockStatusServer

        payload = {"schema_version": 1, "overall_status": "HEALTHY"}
        server = MockStatusServer({"cs": payload}, port=0)

        try:
            server.start()
            result = fetch_status(
                {
                    "base_url": server.base_url("cs"),
                    "timeout_seconds": 3,
                    "api_token": "local-token",
                }
            )
        finally:
            server.stop()

        self.assertEqual(result, payload)

    def test_fetch_status_uses_host_url_and_bearer_token(self):
        from ops.api_provider import fetch_status

        calls = []

        def transport(url, headers, timeout, max_bytes):
            calls.append((url, headers, timeout, max_bytes))
            return 200, b'{"schema_version": 1}'

        host = {
            "base_url": "http://127.0.0.1:9001/api",
            "timeout_seconds": 7,
            "api_token": "local-token",
        }

        result = fetch_status(host, transport=transport)

        self.assertEqual(result, {"schema_version": 1})
        self.assertEqual(calls[0][0], "http://127.0.0.1:9001/api/status")
        self.assertEqual(
            calls[0][1]["Authorization"],
            "Bearer local-token",
        )
        self.assertEqual(calls[0][2], 7)

    def test_fetch_status_rejects_non_success_response(self):
        from ops.api_provider import ApiProviderError, fetch_status

        with self.assertRaises(ApiProviderError):
            fetch_status(
                {
                    "base_url": "http://127.0.0.1:9001/api",
                    "timeout_seconds": 7,
                    "api_token": "local-token",
                },
                transport=lambda *args: (503, b"unavailable"),
            )

    def test_fetch_status_rejects_oversized_response(self):
        from ops.api_provider import ApiProviderError, fetch_status

        with self.assertRaisesRegex(
            ApiProviderError,
            "payload too large",
        ):
            fetch_status(
                {
                    "base_url": "http://127.0.0.1:9001/api",
                    "timeout_seconds": 7,
                    "api_token": "local-token",
                },
                transport=lambda *args: (200, b"X" * (1024 * 1024 + 1)),
            )


if __name__ == "__main__":
    unittest.main()
