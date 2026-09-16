<?php

namespace Oliweb\StatamicAnalytics\Tests;

use Illuminate\Support\Facades\DB;
use Oliweb\StatamicAnalytics\Services\PageViewRecorder;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests unitaires de PageViewRecorder — focus sur le flag skip_geolocation.
 *
 * Provider 'disabled' utilisé pour neutraliser GeolocationService sans mock :
 * cela permet de vérifier le branchement conditionnel sur le résultat DB
 * sans dépendance réseau ni fichier MaxMind.
 */
class PageViewRecorderTest extends TestCase
{
    private function baseRecord(array $overrides = []): array
    {
        return array_merge([
            'event_id'          => (string) \Illuminate\Support\Str::uuid(),
            'page_url'          => '/test',
            'ip_address'        => '203.0.113.1',
            'user_agent'        => 'TestAgent/1.0',
            'device_type'       => 'desktop',
            'browser'           => 'Chrome',
            'platform'          => 'Linux',
            'referrer_url'      => null,
            'user_id'           => null,
            'session_id'        => (string) \Illuminate\Support\Str::uuid(),
            'visitor_id'        => (string) \Illuminate\Support\Str::uuid(),
            'is_new_visitor'    => true,
            'is_new_day_visit'  => true,
            'is_new_hour_visit' => true,
            'is_new_page_visit' => true,
            'visited_at'        => now()->format('Y-m-d H:i:s'),
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $overrides);
    }

    #[Test]
    public function test_skip_geolocation_false_appelle_le_provider(): void
    {
        config(['statamic-analytics.geolocation.provider' => 'disabled']);

        $record = $this->baseRecord(['skip_geolocation' => false]);
        (new PageViewRecorder())->record($record);

        // Provider disabled → null, mais le chemin de code géolocalisation est emprunté
        $row = DB::table('statamic_analytics_page_views')
            ->where('event_id', $record['event_id'])
            ->first();

        $this->assertNotNull($row);
        $this->assertNull($row->country_code);
    }

    #[Test]
    public function test_skip_geolocation_true_insere_geo_null_sans_appel_provider(): void
    {
        // Configurer un provider qui échouerait si appelé (maxmind sans fichier).
        // Si skip_geolocation fonctionne, aucune exception n'est levée.
        config([
            'statamic-analytics.geolocation.provider'              => 'maxmind',
            'statamic-analytics.geolocation.maxmind.database_path' => '/dev/null/inexistant.mmdb',
        ]);

        $record = $this->baseRecord(['skip_geolocation' => true]);
        (new PageViewRecorder())->record($record);

        $row = DB::table('statamic_analytics_page_views')
            ->where('event_id', $record['event_id'])
            ->first();

        $this->assertNotNull($row);
        $this->assertNull($row->country_code);
        $this->assertNull($row->country_name);
        $this->assertNull($row->city);
    }

    #[Test]
    public function test_skip_geolocation_absent_du_payload_insere_le_record(): void
    {
        config(['statamic-analytics.geolocation.provider' => 'disabled']);

        // Sans clé skip_geolocation (payload legacy) → comportement par défaut (pas de skip)
        $record = $this->baseRecord(); // pas de skip_geolocation
        (new PageViewRecorder())->record($record);

        $this->assertDatabaseHas('statamic_analytics_page_views', [
            'event_id' => $record['event_id'],
        ]);
    }

    #[Test]
    public function test_skip_geolocation_non_present_en_base(): void
    {
        config(['statamic-analytics.geolocation.provider' => 'disabled']);

        $record = $this->baseRecord(['skip_geolocation' => true]);
        (new PageViewRecorder())->record($record);

        // La colonne skip_geolocation ne doit pas exister en base (clé interne supprimée)
        $columns = DB::getSchemaBuilder()->getColumnListing('statamic_analytics_page_views');
        $this->assertNotContains('skip_geolocation', $columns);
    }
}
