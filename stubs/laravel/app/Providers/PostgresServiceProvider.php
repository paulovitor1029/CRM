<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\ConnectionCreated;
use Illuminate\Database\Schema\Blueprint;

class PostgresServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Ensure timezone is UTC on each new connection
        Event::listen(ConnectionCreated::class, function (ConnectionCreated $event) {
            if ($event->connection->getDriverName() === 'pgsql') {
                $event->connection->unprepared("SET TIME ZONE 'UTC'");
            }
        });

        // Blueprint macros for UUID primary keys and timestamps in UTC
        Blueprint::macro('uuidPrimary', function (string $column = 'id') {
            /** @var Blueprint $this */
            $fn = env('PG_UUID_FUNCTION', 'gen_random_uuid()');
            $this->uuid($column)->primary()->default(DB::raw($fn));
        });

        Blueprint::macro('timestampsTzUtc', function () {
            /** @var Blueprint $this */
            $this->timestampsTz(0);
        });
    }
}

