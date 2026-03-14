/**
 * Sprint 2 Components Entry Point
 * Importa todos os componentes melhorados do dashboard
 * CSS e JS necessários
 */

// ============ CSS Imports ============
import '../../assets/css/pages/admin-dashboard/health-score.css';
import '../../assets/css/pages/admin-dashboard/greeting.css';

// ============ JS Imports ============
// Garante que os componentes estão disponíveis globalmente
import HealthScoreWidget from './health-score';
import DashboardGreeting from './greeting';
import './components-init';

// Export para uso como módulo
export { HealthScoreWidget, DashboardGreeting };
