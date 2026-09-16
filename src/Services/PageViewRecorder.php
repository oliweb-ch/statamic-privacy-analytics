<?php

namespace Oliweb\StatamicAnalytics\Services;

use Illuminate\Support\Facades\Log;
use Oliweb\StatamicAnalytics\Support\AnalyticsDB;

class PageViewRecorder
{
    public function record(array $data): void
    {
        $skipGeo = (bool) ($data['skip_geolocation'] ?? false);
        $insertData = array_diff_key($data, ['skip_geolocation' => null]);

        $ipAddress = $insertData['ip_address'] ?? '';

        if ($skipGeo) {
            $geoData = ['country_code' => null, 'country_name' => null, 'city' => null];
        } else {
            $geoData = (new GeolocationService())->lookup((string) $ipAddress);
        }

        $inserted = AnalyticsDB::table('statamic_analytics_page_views')->insertOrIgnore(array_merge($insertData, [
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
