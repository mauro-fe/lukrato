/**
 * ============================================================================
 * LUKRATO — Landing Page Base (Barrel / Entry Point)
 * ============================================================================
 * Imports all landing modules and initialises them on DOMContentLoaded.
 * Each module exports an init() function.
 * ============================================================================
 */

import { init as initThemeToggle } from './landing/theme-toggle.js';
import { init as initScrollRouting } from './landing/scroll-routing.js';
import { init as initMenu } from './landing/menu.js';
import { init as initGallery } from './landing/gallery.js';
import { init as initContact } from './landing/contact.js';
import { init as initBackToTop } from './landing/back-to-top.js';
import { init as initCookieConsent } from './landing/cookie-consent.js';
import { init as initLeakCalculator } from './landing/leak-calculator.js';

/* Theme toggle runs immediately (before DOMContentLoaded) so icons match */
initThemeToggle();

function bootstrap() {
    initScrollRouting();
    initMenu();
    initGallery();
    initContact();
    initBackToTop();
    initCookieConsent();
    initLeakCalculator();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
} else {
    bootstrap();
}