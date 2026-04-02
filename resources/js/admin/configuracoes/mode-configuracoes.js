import { initConfigDangerZone } from './config-danger.js';
import { initConfigIntegrations } from './config-integrations.js';
import { initConfigPasswordStrength } from './config-password-strength.js';
import { initConfigReferral } from './config-referral.js';
import { initConfigSecurity } from './config-security.js';
import { loadProfile } from '../perfil/profile-common.js';

export function initConfiguracoesMode(context) {
    initConfigSecurity(context);
    initConfigDangerZone(context);
    initConfigReferral(context);
    initConfigIntegrations(context);
    initConfigPasswordStrength();
    return loadProfile(context);
}
