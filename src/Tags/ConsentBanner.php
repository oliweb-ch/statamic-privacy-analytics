<?php

namespace Oliweb\StatamicAnalytics\Tags;

// use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\File;
use Statamic\Tags\Tags;

class ConsentBanner extends Tags
{
    protected static $handle = 'statamic_analytics';

    /**
     * The {{ statamic_analytics:consent_banner }} tag
     */
    public function consent_banner()
    {
        return $this->index();
    }

    /**
     * The {{ statamic_analytics }} tag
     */
    public function index()
    {
        try {
            // Get the template content directly
            $templatePath = $this->getTemplatePath();
            if (!File::exists($templatePath)) {
                throw new \Exception("Template not found at: {$templatePath}");
            }

            // Get the template content
            $content = File::get($templatePath);

            // Get the context data
            $context = array_merge($this->context->all(), [
                'config' => [
                    'statamic-analytics' => [
                        'tracking' => [
                            'consent' => [
                                'banner' => config('statamic-analytics.tracking.consent.banner')
                            ]
                        ]
                    ]
                ]
            ]);

            // Parse it with Antlers
            return \Statamic\Facades\Antlers::parse($content, $context);
        } catch (\Exception $e) {
            Log::error('Error rendering consent banner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '<!-- Error rendering consent banner: ' . $e->getMessage() . ' -->';
        }
    }

    protected function getTemplatePath()
    {
        $exportedComponentPath = resource_path('views/vendor/statamic-analytics/components/consent-banner.antlers.html');
        if (file_exists($exportedComponentPath)) {
            return $exportedComponentPath;
        } else {
            return __DIR__ . '/../../resources/views/components/consent-banner.antlers.html';
        }
    }

    /**
     * The {{ statamic_analytics:tracker }} tag.
     *
     * Rend un script de tracking JS uniquement si STATAMIC_STATIC_CACHING_STRATEGY=full.
     * Dans tous les autres cas, le middleware TrackPageVisit gère le tracking côté serveur.
     *
     * Le bundle IIFE est compilé par Vite depuis resources/js/tracker-bundle.js
     * (source unique : resources/js/tracker.js) et lu ici via file_get_contents.
     * La config PHP (cr, ep) est transmise au bundle via window.__anl.
     *
     * Prérequis : exécuter `npm run build` avant de déployer.
     */
    public function tracker(): string
    {
        if (config('statamic.static_caching.strategy') !== 'full') {
            return '';
        }

        $bundlePath = __DIR__ . '/../../resources/dist/tracker.js';
        if (!file_exists($bundlePath)) {
            Log::warning('statamic-analytics: bundle tracker absent (' . $bundlePath . '). Lancer : npm run build');
            return '';
        }

        $config = json_encode([
            'cr' => (bool) config('statamic-analytics.tracking.consent.enabled', false),
            'ep' => '/statamic-analytics/track',
        ], JSON_UNESCAPED_SLASHES);
        $script = file_get_contents($bundlePath);

        return <<<HTML
<script>window.__anl={$config};</script>
<script>{$script}</script>
HTML;
    }

    public function wildcard($method)
    {
        if ($method === 'consent_banner') {
            return $this->consent_banner();
        }

        return $this->index();
    }

    /**
     * The {{ AltCookies:AddonAssets }} tag.
     * Puts the Vite assets on the frontent
     * @return string|array
     */
    public function AddonAssets()
    {
//        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/statamic-analytics/dist');
//        //$assets = sprintf('<script data-cfasync=“false” type="module" src="%s"></script>', $vite->asset('resources/js/consent-banner.js'));
//        $assets = sprintf('<link rel="stylesheet" href="%s" />', $vite->asset('resources/css/statamic-analytics.css'));
//        return $assets;
    }
}
