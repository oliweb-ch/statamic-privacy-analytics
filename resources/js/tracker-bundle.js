/**
 * Point d'entrée IIFE du tracker — compilé par Vite en resources/dist/tracker.js.
 *
 * La config PHP (cr, ep) est injectée via window.__anl par ConsentBanner::tracker()
 * juste avant ce script. tracker.js reste la source de référence des fonctions,
 * couverte par Vitest.
 */
import { init } from './tracker.js';

const cfg = window.__anl || {};
init({ endpoint: cfg.ep, consentRequired: cfg.cr });
