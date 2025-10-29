<?php

namespace App\Logging;

use Monolog\LogRecord;

class CorrelationProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        // Ensure request_id/organization_id keys exist in context for every log
        $record->context['request_id'] = $record->context['request_id'] ?? ($_SERVER['HTTP_X_REQUEST_ID'] ?? null);
        $record->context['organization_id'] = $record->context['organization_id'] ?? ($_SERVER['HTTP_X_ORGANIZATION_ID'] ?? null);
        return $record;
    }
}
