<?php

declare(strict_types=1);

namespace Application\UseCases\Perfil;

use Application\Models\Usuario;

class DashboardPreferencesUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function get(Usuario $user): array
    {
        return [
            'preferences' => is_array($user->dashboard_preferences) ? $user->dashboard_preferences : [],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(Usuario $user, array $payload): array
    {
        $allowed = [
            'toggleHealthScore',
            'toggleAiTip',
            'toggleEvolucao',
            'toggleAlertas',
            'toggleGrafico',
            'togglePrevisao',
            'toggleMetas',
            'toggleCartoes',
            'toggleContas',
            'toggleOrcamentos',
            'toggleFaturas',
            'toggleGamificacao',
        ];

        $prefs = is_array($user->dashboard_preferences) ? $user->dashboard_preferences : [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $prefs[$key] = (bool) $payload[$key];
            }
        }

        $user->dashboard_preferences = $prefs;
        $user->save();

        return [
            'preferences' => $prefs,
        ];
    }
}

