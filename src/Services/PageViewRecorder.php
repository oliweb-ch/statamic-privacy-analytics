<?php

namespace Oliweb\StatamicAnalytics\Services;

use Illuminate\Support\Facades\Log;
use Oliweb\StatamicAnalytics\Support\AnalyticsDB;

class PageViewRecorder
{
    public function record(array $data): void
    {
        $ipAddress = $data['ip_address'] ?? '';
        $geoData = (new GeolocationService())->lookup((string) $ipAddress);

        $inserted = AnalyticsDB::table('statamic_analytics_page_views')->insertOrIgnore(array_merge($data, [
            'country_code' => $geoData['country_code'],
            'country_name' => $geoData['country_name'],
            'city'         => $geoData['city'],
        ]));

        if ($inserted === 0) {
            Log::info('StatamicAnalytics: page view ignorée, event_id déjà traité (retry détecté).', [
                'event_id' => $data['event_id'] ?? null,
            ]);
        }
    }
}
