<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;

class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $logger = Log::getLogger();
        foreach ($logger->getHandlers() as $handler) {
            // If handler supports a formatter, force JSON
            if (method_exists($handler, 'setFormatter')) {
                $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true);
                $handler->setFormatter($formatter);
            }
        }
    }
}

