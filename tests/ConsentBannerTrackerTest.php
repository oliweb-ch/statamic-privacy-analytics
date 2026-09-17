<?php

namespace Oliweb\StatamicAnalytics\Tests;

use Oliweb\StatamicAnalytics\Tags\ConsentBanner;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests de ConsentBanner::tracker() après unification Vite.
 *
 * Le bundle IIFE (resources/dist/tracker.js) est créé temporairement dans setUp/tearDown
 * pour les tests qui nécessitent un fichier existant. Les tests de "bundle absent"
 * s'appuient sur l'absence de ce fichier en environnement de test (pas de `npm run build`).
 *
 * Couverture :
 *   1. Stratégie non-full → chaîne vide (middleware gère le tracking)
 *   2. Bundle absent → chaîne vide + log warning (build manquant)
 *   3. Injection de window.__anl avec les bonnes valeurs de config
 *   4. Contenu du bundle injecté verbatim dans la sortie
 */
class ConsentBannerTrackerTest extends TestCase
{
    private string $bundlePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundlePath = __DIR__ . '/../resources/dist/tracker.js';

        if (!is_dir(dirname($this->bundlePath))) {
            mkdir(dirname($this->bundlePath), 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->bundlePath)) {
            unlink($this->bundlePath);
        }
        parent::tearDown();
    }

    private function withBundle(string $content = '(function(){/* stub */})();'): void
    {
        file_put_contents($this->bundlePath, $content);
    }

    private function tracker(): string
    {
        return (new ConsentBanner())->tracker();
    }

    // ── 1. Stratégie non-full ─────────────────────────────────────────────────

    #[Test]
    public function test_tracker_retourne_vide_quand_strategie_non_full(): void
    {
        config(['statamic.static_caching.strategy' => null]);
        $this->assertSame('', $this->tracker());
    }

    #[Test]
    public function test_tracker_retourne_vide_quand_strategie_half(): void
    {
        config(['statamic.static_caching.strategy' => 'half']);
        $this->assertSame('', $this->tracker());
    }

    // ── 2. Bundle absent ──────────────────────────────────────────────────────

    #[Test]
    public function test_tracker_retourne_vide_quand_bundle_absent(): void
    {
        config(['statamic.static_caching.strategy' => 'full']);
        // Pas de withBundle() → le fichier n'existe pas
        $this->assertSame('', $this->tracker());
    }

    // ── 3. Injection de window.__anl ─────────────────────────────────────────

    #[Test]
    public function test_tracker_injecte_cr_false_quand_consent_desactive(): void
    {
        config([
            'statamic.static_caching.strategy'                => 'full',
            'statamic-analytics.tracking.consent.enabled'     => false,
        ]);
        $this->withBundle();

        $output = $this->tracker();

        $this->assertStringContainsString('window.__anl', $output);
        $this->assertStringContainsString('"cr":false', $output);
    }

    #[Test]
    public function test_tracker_injecte_cr_true_quand_consent_active(): void
    {
        config([
            'statamic.static_caching.strategy'                => 'full',
            'statamic-analytics.tracking.consent.enabled'     => true,
        ]);
        $this->withBundle();

        $output = $this->tracker();

        $this->assertStringContainsString('"cr":true', $output);
    }

    #[Test]
    public function test_tracker_injecte_endpoint_par_defaut(): void
    {
        config(['statamic.static_caching.strategy' => 'full']);
        $this->withBundle();

        $this->assertStringContainsString('/statamic-analytics/track', $this->tracker());
    }

    // ── 4. Contenu du bundle ──────────────────────────────────────────────────

    #[Test]
    public function test_tracker_injecte_contenu_du_bundle_verbatim(): void
    {
        config(['statamic.static_caching.strategy' => 'full']);
        $this->withBundle('(function(){console.log("tracker-stub");})();');

        $this->assertStringContainsString('console.log("tracker-stub")', $this->tracker());
    }
}
