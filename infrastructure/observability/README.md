# Observability Stack

This folder provides a local stack for:
- Prometheus (`:9090`)
- Alertmanager (`:9093`)
- Grafana (`:3000`)

## Quick Start

1. Set a metrics token in platform `.env`:
```bash
PROMETHEUS_ENABLED=true
PROMETHEUS_TOKEN=replace-with-strong-token
```

2. Update Prometheus scrape token in `prometheus/prometheus.yml`:
```yaml
bearer_token: "replace-with-strong-token"
```

3. Start stack:
```bash
cd infrastructure/observability
docker compose up -d
```

4. Validate:
- Metrics endpoint: `curl -H "Authorization: Bearer <token>" http://<platform-host>/metrics`
- Prometheus targets: `http://<host>:9090/targets`
- Grafana login: `admin / admin` (change password immediately)

## Notes

- `host.docker.internal:8000` in `prometheus.yml` assumes local app runtime.
- For production, replace target with your real platform hostname and TLS scheme.
- Update `alertmanager/alertmanager.yml` receiver URL to your incident webhook endpoint.

