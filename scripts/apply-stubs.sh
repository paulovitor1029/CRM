#!/usr/bin/env bash
set -euo pipefail

echo "Applying Laravel stubs (providers/config)..."

# Copy providers
mkdir -p app/Providers app/Logging
cp -r stubs/laravel/app/Providers/* app/Providers/
cp -r stubs/laravel/app/Logging/* app/Logging/ || true

# Copy configs (non-destructive: only if not exist)
mkdir -p config
for f in stubs/laravel/config/*; do
  base=$(basename "$f")
  if [ ! -f "config/$base" ]; then
    cp "$f" "config/$base"
  else
    echo "Skipping existing config/$base"
  fi
done

# Root tooling
[ -f phpstan.neon.dist ] || cp stubs/laravel/phpstan.neon.dist phpstan.neon.dist
[ -f pint.json ] || cp stubs/laravel/pint.json pint.json
[ -f phpunit.xml ] || cp stubs/laravel/phpunit.xml phpunit.xml

# Try to register providers in bootstrap/app.php (Laravel 11)
if [ -f bootstrap/app.php ]; then
  if ! grep -q "withProviders" bootstrap/app.php; then
    # Insert before ->create();
    awk '1;/->create\(\);/{print "    ->withProviders([\\n        App\\\\Providers\\\\ObservabilityServiceProvider::class,\\n        App\\\\Providers\\\\PostgresServiceProvider::class,\\n        App\\\\Providers\\\\RedisServiceProvider::class,\\n        App\\\\Providers\\\\AuthServiceProvider::class,\\n        App\\\\Providers\\\\AuthorizationServiceProvider::class,\\n    ])"}' bootstrap/app.php > bootstrap/app.php.tmp
    mv bootstrap/app.php.tmp bootstrap/app.php
    echo "Providers inserted into bootstrap/app.php"
  else
    echo "bootstrap/app.php already has withProviders; please ensure providers are listed."
  fi
else
  echo "bootstrap/app.php not found. Ensure you are in a Laravel app root."
fi

echo "Done."
