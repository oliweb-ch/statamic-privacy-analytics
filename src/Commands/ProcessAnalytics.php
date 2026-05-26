<?php

namespace Oli217\EnhancedAnalytics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAnalytics extends Command
{
    protected $signature = 'analytics:process';
    protected $description = 'Recalculate analytics aggregates from page_views for the last 2 days';

    public function handle()
    {
        $this->info('Processing analytics aggregates...');

        $lock = Cache::lock('enhanced-analytics:processing', config('enhanced-analytics.processing.lock_timeout', 60));

        try {
            if (!$lock->get()) {
                $this->warn('Another process is already running. Skipping...');
                return;
            }

            $dates = [
                Carbon::today()->toDateString(),
                Carbon::yesterday()->toDateString(),
            ];

            foreach ($dates as $date) {
                DB::transaction(function () use ($date) {
                    $this->rebuildAggregatesForDate($date);
                });
            }

            $this->info('Aggregates updated for: ' . implode(', ', $dates));
        } catch (\Exception $e) {
            $this->error("Fatal error during processing: {$e->getMessage()}");
            Log::error('Enhanced Analytics: ProcessAnalytics error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $lock->release();
        }
    }

    protected function rebuildAggregatesForDate(string $date): void
    {
        $dimensions = ['country_code', 'device_type', 'browser', 'platform'];

        foreach ($dimensions as $dimension) {
            // Delete existing aggregates for this date/dimension
            DB::table('enhanced_analytics_aggregates')
                ->where('type', 'daily')
                ->where('date', $date)
                ->where('dimension', $dimension)
                ->delete();

            // Re-insert from page_views
            $rows = DB::table('enhanced_analytics_page_views')
                ->select(
                    DB::raw("'{$dimension}' as dimension"),
                    DB::raw("{$dimension} as dimension_value"),
                    DB::raw('COUNT(*) as total_visits'),
                    DB::raw('SUM(CASE WHEN is_new_visitor = 1 THEN 1 ELSE 0 END) as unique_visitors'),
                    DB::raw('SUM(CASE WHEN is_new_page_visit = 1 THEN 1 ELSE 0 END) as unique_page_views'),
                    DB::raw('SUM(CASE WHEN is_new_visitor = 0 THEN 1 ELSE 0 END) as returning_visitors')
                )
                ->whereDate('visited_at', $date)
                ->whereNotNull($dimension)
                ->where($dimension, '!=', '')
                ->groupBy($dimension)
                ->get();

            $now = Carbon::now();
            foreach ($rows as $row) {
                DB::table('enhanced_analytics_aggregates')->insert([
                    'type'              => 'daily',
                    'date'              => $date,
                    'dimension'         => $dimension,
                    'dimension_value'   => $row->dimension_value,
                    'total_visits'      => $row->total_visits,
                    'unique_visitors'   => $row->unique_visitors,
                    'unique_page_views' => $row->unique_page_views,
                    'returning_visitors'=> $row->returning_visitors,
                    'updated_at'        => $now,
                ]);
            }
        }
    }
}
