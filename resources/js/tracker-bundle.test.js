/**
 * Smoke test du bundle IIFE compilé — resources/dist/tracker.js.
 *
 * Vérifie que le produit Rollup (tracker-bundle.js → IIFE) est réellement exécutable
 * et se comporte correctement dans un environnement navigateur simulé (jsdom).
 *
 * Prérequis : npm run build doit avoir été exécuté.
 * Les tests sont ignorés automatiquement si le bundle est absent.
 */

import { beforeEach, describe, expect, it, vi } from 'vitest';
import { existsSync, readFileSync } from 'fs';
import { resolve } from 'path';

const bundlePath = resolve(process.cwd(), 'resources/dist/tracker.js');
const bundleExists = existsSync(bundlePath);

// Exécute le bundle IIFE dans le contexte jsdom courant.
function execBundle() {
    // eslint-disable-next-line no-new-func
    new Function(readFileSync(bundlePath, 'utf-8'))();
}

describe.skipIf(!bundleExists)('tracker-bundle IIFE (smoke)', () => {
    beforeEach(() => {
        localStorage.clear();
        sessionStorage.clear();
        delete window.__anl;
    });

    it('envoie un beacon vers l\'endpoint configuré dans window.__anl', () => {
        window.__anl = { cr: false, ep: '/test/track' };
        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue(new Response());

        execBundle();

        expect(fetchSpy).toHaveBeenCalledOnce();
        expect(fetchSpy.mock.calls[0][0]).toContain('/test/track');
    });

    it('ne déclenche pas le beacon si cr=true et localStorage vide', () => {
        window.__anl = { cr: true, ep: '/test/track' };
        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue(new Response());

        execBundle();

        expect(fetchSpy).not.toHaveBeenCalled();
    });

    it('déclenche le beacon si cr=true et analytics_consent=accepted', () => {
        window.__anl = { cr: true, ep: '/test/track' };
        localStorage.setItem('analytics_consent', 'accepted');
        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue(new Response());

        execBundle();

        expect(fetchSpy).toHaveBeenCalledOnce();
    });

    it('utilise les valeurs de session existantes sans créer de doublons', () => {
        window.__anl = { cr: false, ep: '/test/track' };
        localStorage.setItem('_anl_vid', 'existing-visitor-id');
        sessionStorage.setItem('_anl_sid', 'existing-session-id');
        vi.spyOn(global, 'fetch').mockResolvedValue(new Response());

        execBundle();

        // Les IDs ne doivent pas avoir changé
        expect(localStorage.getItem('_anl_vid')).toBe('existing-visitor-id');
        expect(sessionStorage.getItem('_anl_sid')).toBe('existing-session-id');
    });
});
