Modular structure (DDD-inspired): each module groups `Domain`, `App` (application/use cases), and `Infra` (adapters).

Suggested layout per module:

modules/
  └── <Context>/
      ├── Domain/
      ├── App/
      └── Infra/

Add PSR-4 autoload in composer.json, e.g.:
"autoload": {
  "psr-4": {
    "Modules\\": "modules/"
  }
}

