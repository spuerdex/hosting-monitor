"""Local-only mock API server for multiple host fixtures."""

import json
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.request import urlopen


class MockStatusServer:
    def __init__(self, fixtures, *, host="127.0.0.1", port=0):
        self._fixtures = fixtures
        self._server = ThreadingHTTPServer(
            (host, port),
            self._handler_factory(),
        )
        self._thread = None

    @property
    def address(self):
        host, port = self._server.server_address
        return host, port

    def base_url(self, code):
        host, port = self.address
        return f"http://{host}:{port}/{code}/api"

    def start(self):
        self._thread = threading.Thread(
            target=self._server.serve_forever,
            daemon=True,
        )
        self._thread.start()

    def stop(self):
        self._server.shutdown()
        self._server.server_close()

        if self._thread is not None:
            self._thread.join(timeout=2)

    def get_status(self, code):
        with urlopen(
            self.base_url(code) + "/status",
            timeout=2,
        ) as response:
            return json.loads(response.read())

    def _handler_factory(self):
        fixtures = self._fixtures

        class Handler(BaseHTTPRequestHandler):
            def do_GET(self):
                prefix, separator, rest = self.path.strip("/").partition("/")

                if separator != "/" or rest != "api/status":
                    self.send_error(404)
                    return

                if prefix not in fixtures:
                    self.send_error(404)
                    return

                payload = json.dumps(
                    fixtures[prefix],
                    ensure_ascii=False,
                ).encode("utf-8")

                self.send_response(200)
                self.send_header("Content-Type", "application/json")
                self.send_header("Content-Length", str(len(payload)))
                self.end_headers()
                self.wfile.write(payload)

            def log_message(self, format, *args):
                return

        return Handler
