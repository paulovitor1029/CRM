<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Observability\MetricsRegistry as Metrics;
use Illuminate\Http\Response;

class MetricsController
{
    public function __invoke()
    {
        // Gauges computed on demand (SLA: tasks overdue)
        $overdue = Task::whereIn('status',['open','in_progress','on_hold','blocked'])
            ->whereNotNull('response_due_at')
            ->where('response_due_at','<', now())
            ->count();
        // Encode as counter with special label since registry lacks gauges in MVP
        Metrics::incCounter('tasks_overdue_gauge', ['tenant' => 'default'], 0); // ensure exists
        $out = Metrics::renderPrometheus();
        $out .= sprintf("tasks_overdue_gauge{tenant=\"default\"} %d\n", $overdue);
        return new Response($out, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }
}

