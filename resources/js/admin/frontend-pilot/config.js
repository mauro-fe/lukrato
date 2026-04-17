import { readBootstrapContext } from '../shared/bootstrap-context.js';

export function readFrontendPilotConfig(root) {
    return {
        context: readBootstrapContext(root),
        endpoints: {},
    };
}