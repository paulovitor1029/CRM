Release Checklist (MVP)

- Security
  - CSP headers present and verified
  - CSRF enabled for web routes; API stateless
  - X-Frame-Options DENY, X-Content-Type-Options nosniff
  - OAuth2 client credentials for public API
  - Webhook signatures (HMAC) verified on receivers (external)

- Multitenancy
  - Endpoints filter by `tenant_id` and return only tenant data
  - Logs, metrics tagged com `tenant_id`

- Observability
  - /api/metrics scraped; dashboards import OK
  - Golden Signals populated in staging traffic

- Data Governance (LGPD)
  - Consents collected and revokable
  - Access logs retrievable by subject
  - Anonymization tested on PII

- Billing & Rules
  - Invoicing math validated (pro-rata, courtesy)
  - PaymentApproved triggers rules/webhooks

- Queues/Chaos
  - Retries and DLQ verified for webhooks and outbox
  - Queue workers supervised and autoscaled

- Backups & Migration
  - DB migrations applied cleanly
  - S3 buckets and keys configured

