# Guia de Instalação e Configuração

Este guia descreve como preparar o ambiente de desenvolvimento e executar o projeto com Docker.

## Pré-requisitos
- Docker e Docker Compose
- Git
- (Opcional) Make

## Passo a passo
1. Clone o repositório
   - `git clone <URL> && cd FastHub`
2. Copie variáveis de ambiente
   - `cp .env.example .env`
   - `cp .env.secrets.example .env.secrets` (edite com seus segredos; não commitar)
3. Suba a stack
   - `make up` ou `./scripts/up.ps1` (Windows) / `./scripts/up.sh` (Unix)
4. Instale dependências PHP (dentro do container)
   - `make composer-install`
5. Gere a APP_KEY
   - `make key`
6. Execute migrações e seeds (opcional)
   - `make migrate` e `make seed`
   - RBAC/ABAC: `php artisan db:seed --class=RbacSeeder`

## Providers e Config do Laravel
- Rode `./scripts/apply-stubs.ps1` (Windows) ou `./scripts/apply-stubs.sh` após criar/atualizar uma app Laravel 11.
- Isso copia providers (observabilidade, postgres, redis) e configurações (`config/logging.php`, `config/cache.php`, `config/database.php`).
- O script tenta registrar os providers em `bootstrap/app.php` (Laravel 11) via `->withProviders([...])`.

## Estrutura de módulos (DDD)
- Coloque o código por contexto em `modules/<Context>/{Domain,App,Infra}`.
- Atualize `composer.json` com `"Modules\\": "modules/"` em `autoload.psr-4` e rode `composer dump-autoload`.

## Execução de Testes
- `make test` para rodar Pest
- `make coverage` para gerar cobertura (HTML/Clove), threshold `--min=80`
- `make lint` para Pint + PHPStan (Larastan)

## Dependências de desenvolvimento (composer)
No root do projeto (dentro do container PHP ou localmente):

```
composer require --dev \
    pestphp/pest pestphp/pest-plugin-laravel \
    nunomaduro/larastan phpstan/phpstan \
    laravel/pint
```
Depois rode: `php artisan pest:install` (se aplicável) e `composer dump-autoload`.

## Variáveis sensíveis
- Use `.env` para valores não sensíveis e `.env.secrets` para segredos (não commitar `.env.secrets`).
- Exemplos estão em `.env.example` e `.env.secrets.example`.

## Configuração de sessão em Redis
- Em `.env`: `SESSION_DRIVER=redis`, `REDIS_HOST=redis`, `REDIS_PORT=6379`.
- Opcional: `REDIS_PASSWORD` se configurado.

## Endpoints de Autenticação
- POST `/api/auth/login`: envia `{ email, password, device_id? }` e header `X-Device-Id` (opcional) para vincular sessão ao dispositivo.
- POST `/api/auth/2fa/verify`: `{ code? (6 dígitos), recovery_code? }` quando 2FA é requerido.
- POST `/api/auth/logout`: encerra sessão.
- POST `/api/auth/refresh`: rotaciona o ID de sessão.
- Todos retornam header `X-Request-Id` para correlação.

## Autorização (RBAC/ABAC)
- Provedor: `AuthorizationServiceProvider` (habilita `Gate::before` com checagem de permissões e atributos, e admin bypass).
- Serviço: `AuthorizationService` (cache de permissões por sessão, ABAC: `setor`, `turno`, `tags`).
- Rotas de exemplo já configuradas:
  - `/api/items` (CRUD protegido por `can:items.*`).
  - `/api/reports/sector/{setor}` (perm `reports.view` + atributo `setor`).

## Setores e Fluxos
- Sectors
  - GET `/api/sectors` — lista setores
  - POST `/api/sectors` — cria (`{ tenant_id?, name, description? }`)
- Flows
  - GET `/api/flows?tenant_id=default` — lista com estados e transições
  - POST `/api/flows` — cria nova versão
    - Exemplo de payload:
      ```json
      {
        "tenant_id": "default",
        "key": "onboarding",
        "name": "Onboarding",
        "states": [
          { "key": "draft", "name": "Draft", "initial": true },
          { "key": "done", "name": "Done", "terminal": true }
        ],
        "transitions": [
          { "key": "submit", "from": "draft", "to": "done" }
        ]
      }
      ```
  - POST `/api/flows/{id}/publish` — publica e congela a versão atual (cria auditoria em `flow_logs`).

## Clientes (Customer)
- Seeds de status: `php artisan db:seed --class=CustomerStatusSeeder`
- Criar cliente:
  - POST `/api/customers`
  - Payload mínimo: `{ "name": "ACME", "status": "ativo" }`
  - Relacionados opcionais: `contacts[]`, `addresses[]`, `tags[]`
- Listar com filtros e cursor:
  - GET `/api/customers?status=ativo&tag=vip&funnel=lead&per_page=10`

## Pipelines/Funis (Clientes)
- Estrutura
  - `pipelines`, `pipeline_stages`, `customer_pipeline_state`, `pipeline_transition_logs`
- Transicionar estágio
  - POST `/api/customers/{id}/transition`
  - Body: `{ "pipeline_key": "vendas", "to_stage": "qualificado", "justification": "verificado" }`
  - Emite evento `CustomerStageChanged` e grava log

## Catálogo (Produtos/Planos/Add-ons/Bundles) e Assinaturas
- Produtos
  - GET `/api/products` | POST `/api/products`
  - Campos: `name`, `sku`, `price_cents`, `currency`, `metadata{}`
- Planos
  - GET `/api/plans` | POST `/api/plans`
  - Campos: `product_id`, `billing_interval` (day|week|month|year), `billing_period`, `trial_days`, `pro_rata`, `courtesy_days`, `limits{}`
- Assinaturas
  - GET `/api/subscriptions` | POST `/api/subscriptions`
  - Campos: `customer_id`, `plan_id?`, `items[]` (tipos: plan|addon|product), `starts_at?`, `trial_ends_at?`, `pro_rata?`, `courtesy_until?`, `limits{}`

## Pendências e Tarefas
- Seeds (opcional): `php artisan db:seed --class=SlaPolicySeeder`
- Configurar limite por usuário: `.env` → `TASKS_MAX_OPEN=5`
- Endpoints principais:
  - GET/POST `/api/tasks`
  - POST `/api/tasks/{id}/assign` (aplica limite por usuário)
  - POST `/api/tasks/{id}/complete` (verifica dependências)
  - GET `/api/tasks/kanban?sector_id=<id>` (visão por setor)
  - GET `/api/tasks/my-agenda` (tarefas do usuário)

## Notificações e Tempo Real
- Broadcasting
  - Configure `BROADCAST_DRIVER` conforme sua infra (ex.: `log`, `pusher`, `redis`).
  - Canais privados definidos em `routes/channels.php` (`tenant.{tenantId}`, `users.{userId}`).
- Web Push
  - Front registra subscription via POST `/api/notifications/subscription` com `{ endpoint, keys:{p256dh,auth}, ... }`.
  - Service Worker manipula push e exibe notificações; backend guarda histórico em `notifications`.
- Central de Notificações
  - GET `/api/notifications` para feed recente; POST `/api/notifications/{id}/read` para marcar como lida.

## Documentos & Arquivos (S3)
- Configurar `.env` para storage S3 (ou local): `FILES_DISK=s3`, `AWS_*`
- Uploads
  - Pré-assinado: POST `/api/files/presign` → PUT direto no S3 com headers retornados
  - Fallback: POST `/api/files/upload?key=...` + multipart `file`
- Documentos (autosave e versões)
  - Criar: POST `/api/documents` (opcional `content` inicial)
  - Autosave: POST `/api/documents/{id}/autosave` com `{ content }`
  - Versões: GET `/api/documents/{id}/versions`, rollback: POST `/api/documents/{id}/versions/{version}/rollback`

## Relatórios & Dashboard
- Materialized Views: criadas via migração; atualizadas a cada 15 min pelo Scheduler.
- Endpoints
  - GET `/api/dashboard/widgets` (KPIs prontos p/ renderização rápida)
  - POST `/api/reports/export` + GET `/api/reports/exports/{id}` para export assíncrona (CSV; compatível com Excel)

## Motor de Regras (MVP)
- Criar regra: POST `/api/rules` com `{ name, event_key, conditions?, actions[] }`
- Simular: POST `/api/rules/simulate` com `{ event_key, payload }`
- Ingerir evento manual: POST `/api/rules/outbox` com `{ event_key, payload }`
- Replay: POST `/api/rules/replay/{outbox_id}`
- Rodando: queue/worker precisa estar ativo para processar `ProcessOutbox`/`ProcessOutboxEvent`

## Webhooks & API Pública
- OAuth2 Client Credentials
  - Crie um client: POST `/api/oauth/clients` (auth interno) → retorna `client_id` e `client_secret`
  - Obtenha token: POST `/api/oauth/token` com `grant_type=client_credentials`
  - Use nas rotas `/api/v1/*` com header `Authorization: Bearer <token>`
- Webhooks
  - Cadastre endpoints: POST `/api/webhooks` com `{ event_key, url, secret? }`
  - Entregas assinadas com HMAC (`X-Webhook-Signature`) e `Idempotency-Key`; retries e DLQ automáticos

## Faturamento Básico
- Emissão de faturas
  - POST `/api/invoices` com `{ subscription_id, bill_at? }`
  - GET `/api/invoices` para listar últimas faturas
- Pagamentos
  - POST `/api/payments` com `{ invoice_id, status (pending|paid|failed), amount_cents, currency?, method?, external_id? }`
  - Ao marcar como paid, evento `PaymentApproved` é emitido (integra com motor de regras)

## LGPD & Segurança
- Consents: POST `/api/privacy/consents` e `/api/privacy/consents/revoke`
- Relatório de acesso por titular: GET `/api/privacy/access-report?subject_type=user|customer&subject_id=<id>`
- Anonimização/pseudonimização: POST `/api/privacy/anonymize` (suporte para `user` e `customer`)
- Políticas de retenção: tabela `data_retention_policies` (configurável por tenant)

## Observabilidade
- Logs JSON com correlação (`request_id`, `tenant_id`, `trace_id`)
- Métricas Prometheus em `/api/metrics` (expor no Prometheus server)
- Dashboards: arquivo `observability/dashboards/grafana-golden-signals.json`

## Importadores (CSV/XLSX)
- Fluxo: upload → map → preview → validate → start
- Endpoints
  - POST `/api/imports/upload` (aceita `file` ou `file_key`, `entity_type`)
  - POST `/api/imports/{id}/map` (ex.: `{ mapping: { name: "Nome", email: "E-mail" } }`)
  - GET `/api/imports/{id}/preview`
  - POST `/api/imports/{id}/validate`
  - POST `/api/imports/{id}/start`
- Erros: GET `/api/imports/{id}/errors` (amostra + `error_report_key` para CSV completo)

## Admin por Tenant
- Branding e configurações: GET `/api/admin/configs`, POST `/api/admin/configs/{scope}`
- Campos customizados: GET/POST `/api/admin/custom-fields`
- Feature flags: GET/POST `/api/admin/feature-flags`
- Templates (push/email/WA): GET/POST `/api/admin/templates`

## Troubleshooting
- Se `vendor/` não existir, execute `make composer-install`.
- Se a aplicação não responder, verifique logs: `make logs`.
- Banco indisponível? Aguarde healthcheck concluir (`compose.yaml` tem healthchecks) ou rode `docker compose ps`.

## Mudan�a Importante
- Billing (planos/assinaturas) removido do sistema (tabelas, models, endpoints, testes).
- Multitenancy agora usa organiza��es. Substitua qualquer uso de tenant_id por organization_id. Contexto ativo via OrganizationContextMiddleware e endpoints /api/organizations. 

