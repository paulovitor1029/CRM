param(
    [switch]$Rebuild
)

if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path .env.secrets)) { Copy-Item .env.secrets.example .env.secrets }

if ($Rebuild) {
  docker compose up -d --build
} else {
  docker compose up -d
}

