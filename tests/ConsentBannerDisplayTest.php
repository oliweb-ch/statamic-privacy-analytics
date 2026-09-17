<?php

namespace Oliweb\StatamicAnalytics\Tests;

use PHPUnit\Framework\Attributes\Test;

/**
 * Tests de rendu de la bannière de consentement.
 *
 * Vérifie que le template Antlers produit le HTML attendu selon la config.
 * Tests directs sur le template (Antlers::parse) — pas de dépendance vers le
 * contexte Statamic Tag pour rester simples et rapides.
 *
 * Couverture :
 *   1. Position (bottom / top / center) → bonnes classes CSS
 *   2. Textes configurables (titre, description, boutons)
 *   3. Alias consent_banner() ≡ index() — même sortie
 *   4. Structure attendue (data-attribute Alpine, rôle ARIA)
 */
class ConsentBannerDisplayTest extends TestCase
{
    private function templatePath(): string
    {
        return __DIR__ . '/../resources/views/components/consent-banner.antlers.html';
    }

    /**
     * Parse le template Antlers avec la config banner fournie.
     */
    private function renderBanner(array $bannerConfig = []): string
    {
        $defaults = [
            'title'           => 'Privacy Notice',
            'description'     => 'Test description.',
            'accept_button'   => 'Accept',
            'decline_button'  => 'Decline',
            'settings_button' => 'Customize',
            'position'        => 'bottom',
        ];

        $banner = array_merge($defaults, $bannerConfig);

        config(['statamic-analytics.tracking.consent.banner' => $banner]);

        $content = file_get_contents($this->templatePath());
        $context = [
            'config' => [
                'statamic-analytics' => [
                    'tracking' => [
                        'consent' => [
                            'banner' => $banner,
                        ],
                    ],
                ],
            ],
        ];

        return \Statamic\Facades\Antlers::parse($content, $context);
    }

    // ── 1. Position ───────────────────────────────────────────────────────────

    #[Test]
    public function test_position_bottom_ajoute_classe_bottom_0(): void
    {
        $html = $this->renderBanner(['position' => 'bottom']);

        // La div racine (class="fixed...") doit contenir bottom-0 mais pas top-0
        $this->assertMatchesRegularExpression('/class="fixed[^"]*bottom-0/', $html);
        $this->assertDoesNotMatchRegularExpression('/class="fixed[^"]*top-0/', $html);
    }

    #[Test]
    public function test_position_top_ajoute_classe_top_0(): void
    {
        $html = $this->renderBanner(['position' => 'top']);

        $this->assertMatchesRegularExpression('/class="fixed[^"]*top-0/', $html);
        $this->assertDoesNotMatchRegularExpression('/class="fixed[^"]*bottom-0/', $html);
    }

    #[Test]
    public function test_position_center_ajoute_classes_top_et_translate(): void
    {
        $html = $this->renderBanner(['position' => 'center']);

        $this->assertStringContainsString('top-1/2', $html);
        $this->assertStringContainsString('-translate-y-1/2', $html);
    }

    // ── 2. Textes configurables ───────────────────────────────────────────────

    #[Test]
    public function test_titre_configurable_present_dans_le_rendu(): void
    {
        $html = $this->renderBanner(['title' => 'Mon titre de confidentialité']);

        $this->assertStringContainsString('Mon titre de confidentialité', $html);
    }

    #[Test]
    public function test_description_configurable_presente_dans_le_rendu(): void
    {
        $html = $this->renderBanner(['description' => 'Ma description de test personnalisée.']);

        $this->assertStringContainsString('Ma description de test personnalisée.', $html);
    }

    #[Test]
    public function test_bouton_accepter_present_dans_le_rendu(): void
    {
        $html = $this->renderBanner(['accept_button' => 'Tout accepter']);

        $this->assertStringContainsString('Tout accepter', $html);
    }

    #[Test]
    public function test_bouton_refuser_present_dans_le_rendu(): void
    {
        $html = $this->renderBanner(['decline_button' => 'Tout refuser']);

        $this->assertStringContainsString('Tout refuser', $html);
    }

    #[Test]
    public function test_bouton_parametres_present_dans_le_rendu(): void
    {
        $html = $this->renderBanner(['settings_button' => 'Personnaliser']);

        $this->assertStringContainsString('Personnaliser', $html);
    }

    // ── 3. Structure attendue ─────────────────────────────────────────────────

    #[Test]
    public function test_composant_alpine_consentbanner_present(): void
    {
        $html = $this->renderBanner();

        // Le composant Alpine est requis pour accept/decline/toggleSettings
        $this->assertStringContainsString('x-data="consentBanner"', $html);
    }

    #[Test]
    public function test_bouton_accepter_declenche_accept_alpine(): void
    {
        $html = $this->renderBanner();

        $this->assertStringContainsString('x-on:click="accept"', $html);
    }

    #[Test]
    public function test_bouton_refuser_declenche_decline_alpine(): void
    {
        $html = $this->renderBanner();

        $this->assertStringContainsString('x-on:click="decline"', $html);
    }

    #[Test]
    public function test_toggle_geolocation_present_avec_role_switch(): void
    {
        $html = $this->renderBanner();

        $this->assertStringContainsString('role="switch"', $html);
        $this->assertStringContainsString('x-on:click="toggleGeolocation"', $html);
    }
}
