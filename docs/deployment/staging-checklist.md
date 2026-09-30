# Staging checklist

- Use a staging API registry with at least two non-production VMs.
- Confirm every host has a unique `code` and its own API token reference.
- Confirm one host timing out leaves the other host visible and current.
- Confirm malformed, oversized and stale responses do not replace the last valid cache.
- Confirm student IDs are scoped by `host_code`.
- Confirm collector permissions allow writes only to the intended cache directory.
- Confirm the PHP portal reads staging cache and never calls VM APIs during page requests.
- Confirm no real student credential, password, private key or production endpoint is present in fixtures/logs.
- Confirm the systemd service and timer run successfully for at least one complete interval.
- Record collector results, cache timestamps and failure metadata before approving production.
