# Production checklist

- Take a configuration backup before changing the registry or service unit.
- Deploy the API registry and token environment through the approved secret mechanism.
- Validate API connectivity from the collector host without exposing tokens in output.
- Run the collector manually once and inspect each per-host metadata file.
- Enable the timer only after the manual run succeeds.
- Verify the PHP-FPM user can read status cache but cannot write Hosting data.
- Verify the collector service can write only `/var/lib/digit-hosting-admin/status`.
- Verify Nginx routes only the portal and static assets; no management endpoint is enabled.
- Monitor one full timer cycle and confirm `last_success`, `fetch_state` and stale behavior.
- Keep rollback copies of registry, service and timer configuration.
- Do not enable browser-based account management in this monitoring phase.
