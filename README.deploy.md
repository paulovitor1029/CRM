Deploy

- Imagens Docker podem ser construídas com `docker build` (veja `compose.yaml`).
- Banco: PostgreSQL 15, timezone UTC. Configure `DB_*` via variáveis de ambiente.
- Cache/Rate Limit: Redis 7 (client phpredis).
- Logs: JSON (Monolog) em `stderr` ou arquivo `storage/logs/laravel.log`.

Passos sugeridos (CI/CD)
- Build → testes → publicar imagens → `php artisan migrate --force`.
- Ajuste secrets no provedor (APP_KEY, DB_PASSWORD, etc.).

