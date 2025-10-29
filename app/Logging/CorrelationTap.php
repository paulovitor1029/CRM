<?php

namespace App\Logging;

use Monolog\Logger;

class CorrelationTap
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new CorrelationProcessor());
    }
}

