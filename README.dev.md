Desenvolvimento

- Pré-requisitos: Docker, Docker Compose, Make (opcional) ou PowerShell/Bash.
- Subir stack: `make init && make up` ou `./scripts/up.ps1` (Windows) / `./scripts/up.sh` (Unix).
- Instalar deps: `make composer-install` (dentro do container PHP).
- Gerar key: `make key`.
- Logs: `make logs`.
- Acessar app: http://localhost:${APP_PORT:-8080}

Comandos úteis
- Shell no PHP: `make bash`
- Migrations: `make migrate`
- Seeds: `make seed`
- DB fresh + seed: `make refresh-db`

