#!/usr/bin/env bash
set -euo pipefail
docker compose exec -T php php artisan migrate --force

