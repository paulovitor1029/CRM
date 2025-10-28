#!/usr/bin/env bash
set -euo pipefail
docker compose exec -T php ./vendor/bin/pest --coverage --min=80
