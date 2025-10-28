Write-Output "Applying Laravel stubs (providers/config)..."

New-Item -ItemType Directory -Force -Path app/Providers | Out-Null
New-Item -ItemType Directory -Force -Path app/Logging | Out-Null

Copy-Item -Recurse -Force stubs/laravel/app/Providers/* app/Providers/ 2>$null
Copy-Item -Recurse -Force stubs/laravel/app/Logging/* app/Logging/ 2>$null

New-Item -ItemType Directory -Force -Path config | Out-Null
Get-ChildItem stubs/laravel/config | ForEach-Object {
  $dest = Join-Path config $_.Name
  if (-not (Test-Path $dest)) { Copy-Item $_.FullName $dest }
}

if (-not (Test-Path phpstan.neon.dist)) { Copy-Item stubs/laravel/phpstan.neon.dist phpstan.neon.dist }
if (-not (Test-Path pint.json)) { Copy-Item stubs/laravel/pint.json pint.json }
if (-not (Test-Path phpunit.xml)) { Copy-Item stubs/laravel/phpunit.xml phpunit.xml }

$bootstrap = "bootstrap/app.php"
if (Test-Path $bootstrap) {
  $content = Get-Content $bootstrap -Raw
  if ($content -notmatch 'withProviders') {
    $updated = $content -replace '->create\(\);', "    ->withProviders([`n        App\\Providers\\ObservabilityServiceProvider::class,`n        App\\Providers\\PostgresServiceProvider::class,`n        App\\Providers\\RedisServiceProvider::class,`n        App\\Providers\\AuthServiceProvider::class,`n        App\\Providers\\AuthorizationServiceProvider::class,`n    ])->create();"
    $updated | Set-Content $bootstrap -NoNewline
    Write-Output "Providers inserted into bootstrap/app.php"
  } else {
    Write-Output "bootstrap/app.php already has withProviders; please ensure providers are listed."
  }
} else {
  Write-Warning "bootstrap/app.php not found. Ensure you are in a Laravel app root."
}

Write-Output "Done."
