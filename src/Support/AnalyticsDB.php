<?php

namespace Oliweb\StatamicAnalytics\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class AnalyticsDB
{
    public static function connection(): Connection
    {
        return DB::connection(config('statamic-analytics.database_connection'));
    }

    public static function table(string $table): Builder
    {
        return static::connection()->table($table);
    }

    public static function schema(): SchemaBuilder
    {
        return Schema::connection(config('statamic-analytics.database_connection'));
    }
}
