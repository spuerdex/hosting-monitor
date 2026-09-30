import unittest


class MockApiServerTests(unittest.TestCase):
    def test_mock_server_serves_status_for_each_host(self):
        from ops.mock_api_server import MockStatusServer

        fixtures = {
            "cs": {"schema_version": 1, "host": "cs"},
            "it": {"schema_version": 1, "host": "it"},
        }

        server = MockStatusServer(fixtures, host="127.0.0.1", port=0)

        try:
            server.start()
            self.assertEqual(
                server.get_status("cs"),
                fixtures["cs"],
            )
            self.assertEqual(
                server.get_status("it"),
                fixtures["it"],
            )
        finally:
            server.stop()


if __name__ == "__main__":
    unittest.main()
