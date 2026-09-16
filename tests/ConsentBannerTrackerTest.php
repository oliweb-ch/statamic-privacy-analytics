<?php

namespace Oliweb\StatamicAnalytics\Tests;

use PHPUnit\Framework\Attributes\Test;

/**
 * Garde-fous de synchronisation entre le script inline de ConsentBanner::tracker()
 * et la spec exécutable resources/js/tracker.js.
 *
 * Ces tests vérifient les invariants comportementaux importants du script inline
 * sans dépendance vers Statamic Tags ni environnement JS.
 *
 * Référence de synchronisation : tracker.js contient les fonctions de référence
 * (todayString, currentHourString) couvertes par Vitest. Le script inline doit
 * implémenter la même logique.
 */
class ConsentBannerTrackerTest extends TestCase
{
    private function trackerSource(): string
    {
        return file_get_contents(__DIR__ . '/../src/Tags/ConsentBanner.php');
    }

    // ── Date/heure ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_script_inline_utilise_date_locale_et_non_utc(): void
    {
        // toISOString() retourne une date UTC (ex: "2024-01-15T22:30:00.000Z").
        // À 00:30 en Europe, la date UTC peut être le jour précédent.
        // Le script doit utiliser getFullYear/getMonth/getDate (temps local).
        $this->assertStringNotContainsString(
            'toISOString',
            $this->trackerSource(),
            'Le script inline ne doit pas utiliser toISOString() — retourne une date UTC, ' .
            'incohérente avec l\'heure locale utilisée pour la clé lh.'
        );
    }

    #[Test]
    public function test_script_inline_utilise_getfull_year_pour_date_locale(): void
    {
        $this->assertStringContainsString(
            'getFullYear',
            $this->trackerSource(),
            'Le script inline doit utiliser getFullYear() pour calculer la date locale, ' .
            'comme tracker.js::todayString().'
        );
    }

    // ── Clés de stockage ───────────────────────────────────────────────────────

    #[Test]
    public function test_cles_de_stockage_presentes_dans_script_inline(): void
    {
        $source = $this->trackerSource();

        // Ces clés doivent être identiques dans tracker.js et le script inline
        // pour assurer la continuité de session lors d'un rechargement.
        foreach (['_anl_vid', '_anl_sid', '_anl_vp', '_anl_ld', '_anl_lh', 'analytics_consent'] as $key) {
            $this->assertStringContainsString(
                $key,
                $source,
                "La clé de stockage '{$key}' doit être présente dans le script inline."
            );
        }
    }

    // ── Consentement ───────────────────────────────────────────────────────────

    #[Test]
    public function test_script_inline_verifie_le_consentement_localStorage(): void
    {
        $this->assertStringContainsString(
            'analytics_consent',
            $this->trackerSource(),
            'Le script inline doit vérifier analytics_consent dans localStorage.'
        );
        $this->assertStringContainsString(
            'accepted',
            $this->trackerSource(),
            'Le script inline doit comparer la valeur à "accepted".'
        );
    }

    // ── visited_pages cap ─────────────────────────────────────────────────────

    #[Test]
    public function test_script_inline_cap_visited_pages_a_20(): void
    {
        // tracker.js::recordVisit() utilise slice(-19) puis push → max 20 entrées.
        // Le script inline doit appliquer la même limite pour éviter la divergence.
        $this->assertStringContainsString(
            'slice(-19)',
            $this->trackerSource(),
            'Le cap de visited_pages doit être slice(-19) pour conserver max 20 entrées, ' .
            'comme dans tracker.js::recordVisit().'
        );
    }
}
