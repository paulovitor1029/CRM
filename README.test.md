Testes

- Rodar testes (Pest): `make test` ou `./scripts/test.ps1` / `./scripts/test.sh`.
- Cobertura: `make coverage` (saída em `coverage/`, threshold >= 80%).
- Lint (Pint + PHPStan): `make lint`.

CI (GitHub Actions)
- Pipeline: lint → tests → build → migrações.
- Arquivo: `.github/workflows/ci.yml`.

Autorização (RBAC/ABAC)
- Seeds: `php artisan db:seed --class=RbacSeeder`
- Testes de autorização: `tests/Feature/Authz/RbacAbacTest.php:1`

