import {
    resolveGamificationAchievementsEndpoint,
    resolveGamificationHistoryEndpoint,
    resolveGamificationLeaderboardEndpoint,
    resolveGamificationMarkAchievementsSeenEndpoint,
    resolveGamificationMissionsEndpoint,
    resolveGamificationPendingAchievementsEndpoint,
    resolveGamificationProgressEndpoint,
    resolveGamificationStatsEndpoint,
} from './gamification.js';

describe('admin/api/endpoints/gamification', () => {
    it('resolve os endpoints v1 da gamificacao', () => {
        expect(resolveGamificationProgressEndpoint()).toBe('api/v1/gamification/progress');
        expect(resolveGamificationAchievementsEndpoint()).toBe('api/v1/gamification/achievements');
        expect(resolveGamificationPendingAchievementsEndpoint()).toBe('api/v1/gamification/achievements/pending');
        expect(resolveGamificationMarkAchievementsSeenEndpoint()).toBe('api/v1/gamification/achievements/mark-seen');
        expect(resolveGamificationStatsEndpoint()).toBe('api/v1/gamification/stats');
        expect(resolveGamificationHistoryEndpoint()).toBe('api/v1/gamification/history');
        expect(resolveGamificationLeaderboardEndpoint()).toBe('api/v1/gamification/leaderboard');
        expect(resolveGamificationMissionsEndpoint()).toBe('api/v1/gamification/missions');
    });
});