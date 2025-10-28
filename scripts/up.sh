#!/usr/bin/env bash
set -euo pipefail
cp -n .env.example .env || true
cp -n .env.secrets.example .env.secrets || true
docker compose up -d --build

