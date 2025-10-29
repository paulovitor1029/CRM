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

## Troubleshooting
- Se `vendor/` não existir, execute `make composer-install`.
- Se a aplicação não responder, verifique logs: `make logs`.
- Banco indisponível? Aguarde healthcheck concluir (`compose.yaml` tem healthchecks) ou rode `docker compose ps`.
