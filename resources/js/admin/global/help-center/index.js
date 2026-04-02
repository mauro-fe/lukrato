import '../../../../css/admin/modules/help-center.css';
import { bootHelpCenter } from './runtime.js';

window.__LK_HELP_CENTER_MANAGED = true;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootHelpCenter);
} else {
    bootHelpCenter();
}
