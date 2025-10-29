# Documentação do Sistema

Este documento descreve a arquitetura, padrões e requisitos de operação do FastHub (Laravel 11 + PostgreSQL + Redis).

## Visão Geral
- Framework: Laravel 11 (PHP 8.3)
- Banco: PostgreSQL 15 (UTC, UUIDs por padrão)
- Cache/Queue/Rate-limit: Redis 7
- Infra: Docker (php-fpm, nginx, postgres, redis)
- Testes: Pest com cobertura
- Qualidade: PSR-12 (Pint), PHPStan (Larastan)
- Estrutura modular: `modules/<Context>/{Domain,App,Infra}`

## Observabilidade
- Logs em JSON (Monolog) via `ObservabilityServiceProvider` e/ou `config/logging.php` com tap.
- Cada linha de log é um JSON contendo nível, mensagem, contexto e timestamp ISO-8601.

## PostgreSQL
- Timezone forçada para UTC em cada conexão (`PostgresServiceProvider`).
- Macros de Blueprint: `uuidPrimary()` e `timestampsTzUtc()`.
- Função UUID configurável por `PG_UUID_FUNCTION` (default: `gen_random_uuid()`).

## Rate limiting / Cache
- Redis como store padrão (`config/cache.php`).
- `RedisServiceProvider` registra rate-limit do grupo `api` (60 req/min por usuário/IP).

## Estrutura Modular
- `modules/Example/Domain`: entidades, value objects, regras de negócio.
- `modules/Example/App`: casos de uso, DTOs, portas.
- `modules/Example/Infra`: repositórios, HTTP, CLI, providers específicos, mapeamentos.
- Ajustar `composer.json` para `"Modules\\": "modules/"`.

## CI/CD
- Workflow: lint → tests → build → migrations (`.github/workflows/ci.yml`).
- Cobertura publicada como artefato (`coverage/`).

## Operação
- Subir stack: `make up`.
- Migrações: `make migrate`. Seeds: `make seed`.
- Testes: `make test` / `make coverage`.
- Lint: `make lint`.

## Autenticação
- Sessões em Redis (`SESSION_DRIVER=redis`).
- Endpoints:
  - POST `/api/auth/login` (email, password[, device_id|X-Device-Id])
    - 200 OK: autenticado; 202 Accepted: 2FA requerido; 401 inválido.
  - POST `/api/auth/2fa/verify` (code ou recovery_code)
    - 200 OK: autenticado; 401 inválido; 400 sem desafio.
  - POST `/api/auth/logout` (auth obrigatório)
    - 204 No Content.
  - POST `/api/auth/refresh` (auth obrigatório)
    - 200 OK e rotação de sessão (mitiga fixation).
- 2FA TOTP: segredo Base32 em `user_security.two_factor_secret`.
- Recovery codes: array JSON em `user_security.two_factor_recovery_codes` (marcação `used_at`).
- Throttle: `throttle:api` aplicado nas rotas.
- Device/session binding: middleware `DeviceSessionEnforcer` valida `X-Device-Id` com `session('device_id')`.
- Política de senha: middleware `EnforcePasswordPolicy` (mín. 12, maiúscula, minúscula, número, símbolo) no login.
- Logs de segurança (JSON): eventos `login_failed`, `2fa_challenge_started`, `2fa_verify_failed`, `login_success`, `login_success_2fa`, `logout`, `session_refreshed` com `request_id` de `RequestIdMiddleware`.

## Esquema de autenticação (migr.)
- `users`: uuid PK, email único, senha, controle de expiração e login.
- `user_security`: 1:1 `users`, 2FA (enabled, secret, recovery_codes, last_2fa_at, tentativa falhas).
- `failed_logins`: auditoria de falhas com ip/user_agent/motivo.

## Autorização (RBAC/ABAC)
- Tabelas:
  - `roles`, `permissions`, `role_permission`, `user_role` para RBAC.
  - `user_attributes` (JSON/JSONB) para atributos: `setor`, `turno`, `tags`.
- Provider central: `AuthorizationServiceProvider` (Gate::before)
  - Admin tem acesso total.
  - Qualquer ability no Gate é tratado como permissão; atributos podem ser passados como 2º argumento.
- Serviço: `AuthorizationService`
  - Cache de permissões por sessão (`auth.permissions`).
  - `can($user, $permission, $attributes=[])` com checagens de atributos.
- Helpers
  - Usar nativos do Laravel: `can()`, `Gate::allows()`, `Gate::authorize()`.
  - Middleware: `can:<permission>` em rotas.
- Seeds básicos: `database/seeders/RbacSeeder.php:1` (admin, gestor_setor, agente, financeiro, suporte) e permissões de exemplo (`items.*`, `reports.view`).
- Rotas de exemplo protegidas:
  - CRUD `/api/items` com `can:items.*`.
  - `/api/reports/sector/{setor}` exige `reports.view` + ABAC `setor` via `Gate::authorize('reports.view', ['setor'=>...])`.

## Setores e Fluxos
- Tabelas
  - `sectors` (unicidade por `tenant_id` + `name`)
  - `flow_definitions` (`tenant_id`, `key`, `version`, `frozen`, `published_at`)
  - `flow_states` (chaves únicas por fluxo, `initial`, `terminal`)
  - `flow_transitions` (referenciam estados do mesmo fluxo)
  - `flow_logs` (auditoria:
    - `publish` gera log com `details={ key, version }`)
- Endpoints
  - GET `/api/sectors` — lista setores
  - POST `/api/sectors` — cria setor (`tenant_id` default `default`)
  - GET `/api/flows` — lista definições (por `tenant_id` query param, default `default`)
  - POST `/api/flows` — cria nova versão de um fluxo (valida 1+ inicial e 1+ terminal; transições referem estados válidos)
  - POST `/api/flows/{id}/publish` — publica e congela a versão (não pode publicar novamente)
- Versionamento e publicação
  - `POST /flows` sempre cria nova versão (`version = max + 1` por `tenant_id,key`).
  - Publicar congela (`frozen=true`) e define `published_at`; cria entrada em `flow_logs`.

## Editor Visual de Fluxos (Contrato)
- DTO (JSON) esperado em `/api/flows/{id}/design`:
  - `nodes: [{ key, name, initial?, terminal? }]`
  - `edges: [{ key, from, to, conditions?: [{ type: 'always' | 'attribute_equals' | 'tag_in', params? }], trigger?: { type: 'manual' | 'event', name? } }]`
- Regras de validação
  - Pelo menos 1 estado inicial e 1 terminal
  - Todos os estados devem ser alcançáveis a partir de algum inicial
  - Transições devem referenciar nodes existentes
  - Condições somente dos tipos citados; parâmetros obrigatórios:
    - `attribute_equals`: `params = { attribute, value }`
    - `tag_in`: `params = { tags: string[] }`
    - `always`: sem parâmetros
  - Triggers suportados: `manual` ou `event` (quando `event`, requer `name`)
- Publicação
  - `POST /api/flows/{id}/publish` valida o grafo salvo e reconstrói `flow_states` e `flow_transitions`, congela a versão e audita em `flow_logs`.
- Exemplo (Novo→Financeiro→Suporte, Teste→Suporte)
  - Nodes: `novo (initial)`, `teste (initial)`, `financeiro`, `suporte (terminal)`
  - Edges: `novo→financeiro (always)`, `financeiro→suporte (attribute_equals setor=financeiro)`, `teste→suporte (tag_in ['qa','lab'])`

## Clientes (Customer)
- Domínio e Tabelas
  - `customers` (tenant_id, external_id?, name, email, phone, status, funnel_stage, meta)
  - `customer_contacts` (type, value, preferred, meta)
  - `addresses` (type, line1, city, ...)
  - `customer_tags` (tag)
  - `customer_history` (action, before, after, user_id, origin, timestamps)
  - `customer_statuses` (parametrização por tenant; seeds em `CustomerStatusSeeder`)
- API
  - GET `/api/customers` (filtros: `tenant_id?`, `status?`, `tag?`, `funnel?`; paginação cursor com `per_page`)
  - POST `/api/customers` (cria cliente + relacionamentos; audita em `customer_history` com before/after, who/when/origem)
- Critérios e validações
  - `status` deve existir em `customer_statuses` para o tenant
  - Auditoria inclui `user_id` (se autenticado), `origin` (campo ou `X-Origin`/`User-Agent`), `request_id` no contexto de log
  - Filtros cobertos por testes (status, tag, funil)

## Pipelines/Funis e Transições de Cliente
- Tabelas
  - `pipelines` (por tenant; `key` ex.: vendas, implantacao, suporte)
  - `pipeline_stages` (estágios com `initial`, `terminal`, `position`)
  - `customer_pipeline_state` (estado atual por cliente+pipeline)
  - `pipeline_transition_logs` (from→to, justification, user_id, origin, timestamps)
- Endpoint
  - POST `/api/customers/{id}/transition` — body: `{ pipeline_key, to_stage, justification?, origin? }`
- Regras
  - Transição manual permitida (motor de regras: placeholder para evolução)
  - Logs de transição gravados e evento emitido
- Eventos
  - `App\Events\CustomerStageChanged` com dados do antes/depois; pronto para integrações/notificações

## Setores e Fluxos
- Tabelas
  - `sectors` (unicidade por `tenant_id` + `name`)
  - `flow_definitions` (`tenant_id`, `key`, `version`, `frozen`, `published_at`)
  - `flow_states` (chaves únicas por fluxo, `initial`, `terminal`)
  - `flow_transitions` (referenciam estados do mesmo fluxo)
  - `flow_logs` (auditoria:
    - `publish` gera log com `details={ key, version }`)
- Endpoints
  - GET `/api/sectors` — lista setores
  - POST `/api/sectors` — cria setor (`tenant_id` default `default`)
  - GET `/api/flows` — lista definições (por `tenant_id` query param, default `default`)
  - POST `/api/flows` — cria nova versão de um fluxo (valida 1+ inicial e 1+ terminal; transições referem estados válidos)
  - POST `/api/flows/{id}/publish` — publica e congela a versão (não pode publicar novamente)
- Versionamento e publicação
  - `POST /flows` sempre cria nova versão (`version = max + 1` por `tenant_id,key`).
  - Publicar congela (`frozen=true`) e define `published_at`; cria entrada em `flow_logs`.
## Pendências e Tarefas
- Tabelas: `sla_policies`, `tasks`, `pendings`, `task_comments`, `task_checklist_items`, `task_labels` (+ pivot), `task_history`.
- SLA: `response_due_at` e `resolution_due_at` calculados de `sla_policy` (por tenant).
- Limite por usuário: `TASKS_MAX_OPEN` (config `tasks.max_open_assignments`).
- Dependências: `depends_on_task_id` impede concluir antes da dependência.
- Recorrência: estrutura JSON em `tasks.recurrence` para evolução.
- Endpoints:
  - GET/POST `/api/tasks`
  - POST `/api/tasks/{id}/assign`
  - POST `/api/tasks/{id}/complete`
  - GET `/api/tasks/kanban?sector_id=<id>` → contrato: `{ columns: { open[], in_progress[], on_hold[], blocked[], done[], canceled[] } }`
  - GET `/api/tasks/my-agenda` → tarefas abertas do usuário autenticado
- Auditoria: `task_history` com `create`, `assign`, `complete` (before/after, user_id, origin, timestamps)

## Notificações Push + Broadcasting (Tempo Real)
- Tabelas
  - `notifications` (tenant_id, user_id?, type, title, body, data{}, channel, read_at, delivered_at)
  - `user_notification_prefs` (por usuário: `preferences{ quiet_hours, enabled_types[], channels[] }`, `push_subscriptions[]`)
  - `notification_templates` (por tenant: `key`, `title`, `body`)
- Eventos (disparo e broadcast)
  - `task.created`, `task.assigned`, `task.completed` (broadcast privado para `tenant.{tenantId}` e/ou `users.{userId}`)
  - `customer.stage.changed` (mudança de etapa em pipeline)
  - SLA próximo/menção: preparar evento para evolução (scheduler/worker)
- Broadcasting
  - Canais: `tenant.{tenantId}` (privado), `users.{userId}` (privado). Definições em `routes/channels.php`.
  - Front pode usar Laravel Echo/WebSocket; SSE opcional conforme driver.
- Web Push (contrato)
  - Endpoint: POST `/api/notifications/subscription` com `{ endpoint, keys:{ p256dh, auth }, browser?, platform? }`
  - Service Worker: deve receber payload `{ title, body, data }` e exibir push; fallback: notificação in-app (toast)
- API da Central
  - GET `/api/notifications` (feed do usuário + do tenant)
  - POST `/api/notifications/{id}/read` (marca como lida)
- Horários de silêncio (quiet hours)
  - Configurados em `user_notification_prefs.preferences.quiet_hours` (start, end, timezone). Backend sempre grava in-app; push pode ser suprimido pelo front conforme prefs.

## Documentos & Arquivos (S3, Auto-save, Versionamento)
- Tabelas
  - `file_objects` (chave, disk, size, content_type, checksum, uploaded_at, meta)
  - `documents` (title, content corrente, current_version, autosave_at, sector_id?, meta)
  - `document_versions` (version, content, created_by, created_at)
  - `document_shares` (por papel `role_name` e/ou `sector_id`, `can_edit`)
- Storage
  - Disk configurável via `FILES_DISK` (S3-compatível). Presign de upload (PUT) quando S3 disponível; fallback para upload via backend.
- Endpoints (Files)
  - GET `/api/files` — lista arquivos (últimos 50)
  - POST `/api/files/presign` — body `{ key, content_type?, size?, checksum?, meta? }` → `{ key, upload_url, headers }`
  - POST `/api/files/upload` — fallback multipart com `file` e query `?key=...`
- Endpoints (Documents)
  - GET/POST `/api/documents`, GET/PUT/DELETE `/api/documents/{id}`
  - POST `/api/documents/{id}/autosave` — cria nova versão transacional (incrementa `current_version`)
  - GET `/api/documents/{id}/versions`, POST `/api/documents/{id}/versions/{version}/rollback`
- Critérios
  - Auto‑save transacional: cria registro em `document_versions` e atualiza `documents`
  - Histórico recuperável via `versions` e `rollback`
  - Acesso por compartilhamento (`document_shares`) por papel/setor (base p/ Policy)

## Relatórios & Dashboard (KPIs + Visões Materializadas)
- Visões Materializadas (PostgreSQL)
  - `mrr_por_mes`: soma mensal do MRR (assinaturas ativas/trial) por tenant
  - `churn_por_mes`: assinaturas canceladas por mês
  - `aging_pendencias`: pendências abertas por faixas de tempo (lt_24h, 1_3d, gt_3d)
  - `produtividade_setor`: tarefas concluídas por setor por dia
  - `conversoes_funil`: contagem de transições por pipeline por dia
- Endpoints
  - GET `/api/dashboard/widgets` → retorna payload com `mrr`, `churn`, `aging`, `prod`, `funnel`
  - POST `/api/reports/export` → body `{ report_key, format (csv|xlsx|pdf), params? }` cria exportação assíncrona
  - GET `/api/reports/exports/{id}` → polling de status (`pending|processing|completed|failed`) e `file_key` quando pronto
- Jobs
  - `RefreshMaterializedViews` agenda atualização a cada 15 min (Scheduler)
- Critérios
  - Consultas rápidas para widgets graças às materialized views (índices por tenant e período)
  - Exportações assíncronas via CSV (compatível com Excel); `xlsx/pdf` podem ser mapeados para CSV inicialmente

## Motor de Regras (Event-Driven) — MVP
- Tabelas
  - `rule_definitions` (tenant_id, name, event_key, conditions[], enabled)
  - `rule_actions` (rule_id, type [create_task|change_stage|send_notification|webhook], position, params{})
  - `outbox` (event_key, payload{}, status, attempts, last_error, processed_at)
  - `rule_runs` (rule_id, outbox_id, status, attempts, logs{}, started_at, finished_at) com unique (rule_id, outbox_id)
- Eventos de Domínio
  - Exemplos implementados: `task.created`, `task.assigned`, `task.completed`, `customer.created`, `customer.stage.changed`, `payment.approved`
  - Listener `OutboxEventRecorder` persiste em `outbox` para reprocessamento/replay
- Execução
  - Job `ProcessOutbox` enfileira pendentes; `ProcessOutboxEvent` avalia regras e executa ações (idempotência via `rule_runs`)
  - DLQ: falhas marcadas em `rule_runs.status=failed` e `outbox.status` atualizado
- Ações suportadas
  - `create_task` (usa `TaskService`)
  - `change_stage` (usa `PipelineService`)
  - `send_notification` (registra em `notifications`)
  - `webhook` (HTTP; controlado por `rules.webhooks_enabled`)
- API
  - POST `/api/rules` (criar regra com ações)
  - POST `/api/rules/simulate` (simular matching de regras para `{ event_key, payload }`)
  - POST `/api/rules/outbox` (ingerir evento manualmente)
  - POST `/api/rules/replay/{id}` (reprocessar outbox)
  - GET `/api/rules/runs` (últimas execuções)
- Critérios
  - Simulação de regra e logs detalhados (`rule_runs.logs`)
  - Reprocessamento (replay) suportado

## Webhooks & API Pública (OAuth2 Client Credentials)
- OAuth2 (Client Credentials)
  - Endpoint: POST `/api/oauth/token` com `grant_type=client_credentials` e credenciais via body ou `Authorization: Basic base64(id:secret)`
  - Resposta: `{ access_token, token_type: 'Bearer', expires_in }`
  - Rotas públicas sob `/api/v1/*` exigem `Authorization: Bearer <token>`.
- Cadastro de Webhooks
  - Tabela `webhook_endpoints` por `tenant_id` e `event_key` (`task.assigned`, `task.completed`, `customer.stage.changed`, etc.)
  - Endpoint: GET/POST `/api/webhooks` (interno, auth) e GET `/api/webhooks/deliveries`
- Entrega (Outbound)
  - HMAC SHA-256: header `X-Webhook-Signature: sha256=<hex(hmac(secret, timestamp+'.'+body))>`
  - Headers: `X-Webhook-Event`, `X-Webhook-Id`, `X-Webhook-Timestamp`, `Idempotency-Key`
  - Retries exponenciais (1m,2m,4m,...) com DLQ após `webhooks.max_attempts` (default 8)
  - Fila: jobs `DispatchPendingWebhooks` e `DispatchWebhook`
- Segurança
  - Proteção contra replay via `X-Webhook-Timestamp` (o receptor deve validar janela)
  - Idempotência garantida por `Idempotency-Key` e `unique(endpoint_id, outbox_id)`

## Faturamento Básico (Faturas, Pagamentos)
- Tabelas
  - `invoices` (tenant, customer, subscription?, period_start/end, status, subtotal/discount/courtesy/total em centavos)
  - `invoice_items` (tipo: plan|addon|product|prorate|courtesy|adjustment; quantity, unit_price_cents, total_cents)
  - `payments` (invoice_id, status: pending|paid|failed, amount_cents, method, paid_at, external_id, erro)
  - `invoice_logs` (auditoria before/after; actions: issue|payment|update|cancel)
- Regras
  - Pró‑rata: quando `subscription.starts_at` no meio do mês e `pro_rata=true`, gera item `prorate` proporcional aos dias ativos no período
  - Ciclos: mensal (base), anual pode ser tratado via planos/itens; MVP cobre mensal (um período)
  - Cortesias: `subscription.courtesy_until >= period_end` zera valores com item `courtesy`
- Endpoints
  - GET `/api/invoices` (listar)
  - POST `/api/invoices` (emitir para `subscription_id` e `bill_at?`)
  - POST `/api/payments` (registrar status do pagamento)
- Integração com Regras
  - Ao registrar `payments.status=paid` → evento `PaymentApproved` é disparado e cai no `outbox` (motor de regras)
- Critérios
  - Cálculo conferível (tests cobrem emissão, cortesia e disparo de pagamento aprovado)
  - Auditoria em `invoice_logs`

## LGPD & Segurança (Auditoria, Consentimento, DPO)
- Tabelas
  - `privacy_consents` (subject_type/id, purpose, version, given_at, revoked_at, ip, user_agent, metadata)
  - `access_logs` (trilha imutável: subject, actor, action, resource, fields, ip, user_agent, occurred_at)
  - `data_retention_policies` (por entidade: retention_days, action [anonymize|delete], conditions?, active)
- Endpoints
  - POST `/api/privacy/consents` e POST `/api/privacy/consents/revoke`
  - GET `/api/privacy/access-report?subject_type=&subject_id=` (paginado)
  - POST `/api/privacy/anonymize` com `{ subject_type: user|customer, subject_id }`
- Políticas de Export/Delete (direito ao esquecimento)
  - MVP: `anonymize` implementado (pseudonimização removendo PII principal); export pode usar endpoints existentes (ex.: `customers` + `documents`) ou evoluir com exportador dedicado
- Critérios
  - Access logs gravados como append‑only na aplicação e consultáveis por titular
- Consents versionados, com IP e user‑agent registrados

## Observabilidade (Logs, Métricas, Traces)
- Logs estruturados
  - JSON via Monolog com `request_id`, `tenant_id` e `trace_id` correlacionados (middlewares RequestId, TenantContext, TraceContext + CorrelationTap)
- Métricas (Prometheus)
  - Endpoint: GET `/api/metrics` (text format). Métricas:
    - `http_requests_total{method,route,status,tenant}`
    - `http_request_duration_seconds_bucket{le,method,route,status,tenant}` (histogram)
    - `http_errors_total{route,status,tenant}`
    - `db_query_duration_seconds_bucket{le}` (histogram)
    - `queue_jobs_processed_total{queue}`, `queue_jobs_failed_total{queue}`
    - `tasks_overdue_gauge{tenant}` (calculada on‑demand)
- Tracing (OpenTelemetry ready)
  - Middleware `TraceContextMiddleware` lê/gera `traceparent`, injeta `trace_id` em logs e propaga via header de resposta
  - DB/Queue instrumentados para métricas de latência; integração com OTel pode ser adicionada por exporter
- Dashboards (exemplo)
  - `observability/dashboards/grafana-golden-signals.json` com painéis de tráfego, latência (p95), erros, jobs e tasks overdue
- Critérios
  - Golden signals instrumentados (latência, tráfego, erros, saturação) e testáveis via `/api/metrics`

## Importadores (CSV/XLSX) com Preview e Validação
- Pipeline
  - Upload (`/api/imports/upload`) → Mapeamento (`/api/imports/{id}/map`) → Pré‑visualização (`/api/imports/{id}/preview`) → Validação (`/api/imports/{id}/validate`) → Import (`/api/imports/{id}/start`)
- Suporte
  - Entidades: `customers`, `products`, `contacts`
  - Mapeamento de colunas: headers do CSV para campos do domínio (ex.: `name`, `email`, `phone`, `status`, `sku`, `price_cents`)
- Validação
  - Pré‑visualização retorna amostra com erros por linha
  - Validação de arquivo: conta `total/valid/invalid`, grava até 200 erros em `import_job_errors` e gera CSV de erros (`error_report_key`)
- Importação
  - Jobs assíncronos: `ImportValidateJob`, `ImportOrchestratorJob`, `ImportProcessChunkJob` (chunks de 2.000 linhas; escalável 100k+)
  - Rollback transacional por linha/chunk (MVP: transação por linha na camada de persistência)
  - Reprocessamento: reencaminhar `/start` após correções/mapeamento
- Tabelas
  - `import_jobs` (status: uploaded|mapped|validating|validated|processing|completed|failed; counts, mapping, file_key)
  - `import_job_errors` (amostra de erros com `row_number`, `errors[]`, `row_data{}`)
- Critérios
  - Suporta CSV nativamente; XLSX pode ser adicionado com biblioteca compatível
  - Relatórios de erro e trilha auditável (status, tempos, contagens)

## Admin & Configurações por Tenant
- Branding e Operação
  - `tenant_configs` com `scope`: `branding` (logo/cores), `domains` (domínios), `timezone` (TZ padrão), `holidays` (datas), `numbering` (regras de numeração)
  - Endpoints: GET `/api/admin/configs`, POST `/api/admin/configs/{scope}` (versões com logs `tenant_config_logs`)
- Templates
  - `message_templates` por canal (`push|email|wa`) e `key`; versão e logs (`message_template_logs`)
  - Endpoints: GET/POST `/api/admin/templates`
- Campos Customizados
  - Tabela `tenant_custom_fields` (entity, name, key, type: string|number|boolean|date|enum; required; visibility por papel; options; order; active; version)
  - Logs em `tenant_custom_field_logs`
  - Endpoints: GET/POST `/api/admin/custom-fields`
- Feature Flags
  - `tenant_feature_flags` (flag_key, enabled, version) com `tenant_feature_flag_logs`
  - Endpoints: GET/POST `/api/admin/feature-flags`
- Critérios
  - Todas as mudanças versionadas e auditáveis (quem alterou, antes/depois, quando)

## Hardening Final + Testes de Isolamento
- Suites dedicadas
  - Multitenancy: `tests/Feature/Multitenancy/IsolationTest.php:1` garante isolamento por `tenant_id`
  - Concorrência em tarefas: `tests/Feature/Tasks/TaskConcurrencyTest.php:1` previne double-assign com claim atômico
  - Integridade dos fluxos: validações de reachability já cobertas em `tests/Feature/Flow/*`
  - Segurança: `tests/Feature/Security/SecurityHeadersTest.php:1` (CSP, XFO, XCTO); CSRF em rotas web (padrão Laravel), API stateless
  - Chaos (filas): testes de retries e DLQ em webhooks (`WebhookSecurityTest`) e regras
- Middleware de segurança e observabilidade
  - `SecurityHeadersMiddleware` aplica CSP/XFO/XCTO/Referrer-Policy/Permissions-Policy
  - Grupo de rotas usa: RequestId, TraceContext, TenantContext, HttpMetrics, SecurityHeaders
- Critérios
  - Cobertura >95% em fluxos‑chave (autenticação, regras, faturamento, tasks, importadores, observabilidade)
  - Checklist de release: `RELEASE_CHECKLIST.md`

## Mudan�a Importante
- Removidos planos/assinaturas e faturamento autom�tico.
- Introduzido modelo de Organiza��es: use organization_id em vez de tenant_id para todas as entidades e filtros.
- Superadmin global cria e gerencia organiza��es e seus usu�rios.

