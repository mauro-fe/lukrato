<?php

declare(strict_types=1);

namespace Application\Support\Admin;

final class PageCustomizerCatalog
{
    /**
     * @return array<string,mixed>
     */
    public static function resolve(string $pageKey): array
    {
        $normalizedPageKey = strtolower(trim($pageKey));

        return match ($normalizedPageKey) {
            'dashboard' => self::dashboard(),
            default => self::empty($normalizedPageKey),
        };
    }

    /**
     * @return array<string,bool>
     */
    public static function preferencesForPreset(string $pageKey, string $preset): array
    {
        $descriptor = self::resolve($pageKey);
        $items = is_array($descriptor['items'] ?? null) ? $descriptor['items'] : [];
        $presetKey = strtolower(trim($preset)) === 'complete' ? 'defaultComplete' : 'defaultEssential';
        $preferences = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $toggleId = trim((string) ($item['id'] ?? ''));
            if ($toggleId === '') {
                continue;
            }

            $preferences[$toggleId] = (bool) ($item[$presetKey] ?? false);
        }

        return $preferences;
    }

    /**
     * @return array<string,string>
     */
    public static function sectionMap(string $pageKey): array
    {
        $descriptor = self::resolve($pageKey);
        $items = is_array($descriptor['items'] ?? null) ? $descriptor['items'] : [];
        $sectionMap = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $toggleId = trim((string) ($item['id'] ?? ''));
            $sectionId = trim((string) ($item['sectionId'] ?? ''));
            if ($toggleId === '' || $sectionId === '') {
                continue;
            }

            $sectionMap[$toggleId] = $sectionId;
        }

        return $sectionMap;
    }

    /**
     * @return list<string>
     */
    public static function toggleKeys(string $pageKey): array
    {
        return array_keys(self::sectionMap($pageKey));
    }

    /**
     * @return list<string>
     */
    public static function gridToggleKeys(string $pageKey): array
    {
        $descriptor = self::resolve($pageKey);
        $items = is_array($descriptor['items'] ?? null) ? $descriptor['items'] : [];
        $gridToggleKeys = [];

        foreach ($items as $item) {
            if (!is_array($item) || !($item['grid'] ?? false)) {
                continue;
            }

            $toggleId = trim((string) ($item['id'] ?? ''));
            if ($toggleId !== '') {
                $gridToggleKeys[] = $toggleId;
            }
        }

        return $gridToggleKeys;
    }

    /**
     * @return array<string,mixed>
     */
    private static function dashboard(): array
    {
        $items = [
            [
                'id' => 'toggleAlertas',
                'sectionId' => 'sectionAlertas',
                'group' => 'primary',
                'label' => 'Alertas',
                'description' => 'Avisos importantes para agir antes que virem problema.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleHealthScore',
                'sectionId' => 'sectionHealthScore',
                'group' => 'primary',
                'label' => 'Saude financeira',
                'description' => 'Resumo rapido do momento atual das suas financas.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleAiTip',
                'sectionId' => 'sectionAiTip',
                'group' => 'primary',
                'label' => 'Dicas do Lukrato',
                'description' => 'Insights e recomendacoes geradas com base no seu cenario.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleEvolucao',
                'sectionId' => 'sectionEvolucao',
                'group' => 'primary',
                'label' => 'Evolucao financeira',
                'description' => 'Acompanhe a tendencia da sua performance ao longo do tempo.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'togglePrevisao',
                'sectionId' => 'sectionPrevisao',
                'group' => 'primary',
                'label' => 'Previsao financeira',
                'description' => 'Veja o impacto projetado das proximas movimentacoes.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleGrafico',
                'sectionId' => 'chart-section',
                'group' => 'primary',
                'label' => 'Grafico de categorias',
                'description' => 'Distribuicao visual dos seus gastos por categoria.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleMetas',
                'sectionId' => 'sectionMetas',
                'group' => 'extras',
                'label' => 'Metas',
                'description' => 'Seus objetivos ativos e quanto falta para concluir cada um.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleCartoes',
                'sectionId' => 'sectionCartoes',
                'group' => 'extras',
                'label' => 'Cartoes',
                'description' => 'Limites, uso atual e visao rapida dos seus cartoes.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleContas',
                'sectionId' => 'sectionContas',
                'group' => 'extras',
                'label' => 'Contas',
                'description' => 'Saldo consolidado e status das contas conectadas.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleOrcamentos',
                'sectionId' => 'sectionOrcamentos',
                'group' => 'extras',
                'label' => 'Orcamentos',
                'description' => 'Controle visual do que ja consumiu em cada orcamento.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleFaturas',
                'sectionId' => 'sectionFaturas',
                'group' => 'extras',
                'label' => 'Faturas de cartao',
                'description' => 'Proximos fechamentos e situacao das faturas abertas.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleGamificacao',
                'sectionId' => 'sectionGamificacao',
                'group' => 'extras',
                'label' => 'Gamificacao',
                'description' => 'Nivel, progresso e incentivos para manter sua rotina.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'dashboard',
            'title' => 'Personalizar dashboard',
            'description' => 'Comece no modo essencial e ative extras quando fizer sentido para voce.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeDashboard',
                'wrapperClass' => 'dash-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'customizeModalOverlay',
                'title' => 'customizeModalTitle',
                'description' => 'customizeModalDescription',
                'close' => 'btnCloseCustomize',
                'save' => 'btnSaveCustomize',
                'presetEssential' => 'btnPresetEssencial',
                'presetComplete' => 'btnPresetCompleto',
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'primary', 'title' => 'Principais'],
                    ['key' => 'extras', 'title' => 'Extras'],
                ],
                $items
            ),
        ];
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return array<string,string>
     */
    private static function sectionMapFromItems(array $items): array
    {
        $sectionMap = [];

        foreach ($items as $item) {
            $toggleId = trim((string) ($item['id'] ?? ''));
            $sectionId = trim((string) ($item['sectionId'] ?? ''));

            if ($toggleId === '' || $sectionId === '') {
                continue;
            }

            $sectionMap[$toggleId] = $sectionId;
        }

        return $sectionMap;
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<string>
     */
    private static function gridToggleKeysFromItems(array $items): array
    {
        $gridToggleKeys = [];

        foreach ($items as $item) {
            if (!($item['grid'] ?? false)) {
                continue;
            }

            $toggleId = trim((string) ($item['id'] ?? ''));
            if ($toggleId !== '') {
                $gridToggleKeys[] = $toggleId;
            }
        }

        return $gridToggleKeys;
    }

    /**
     * @param list<array{key:string,title:string}> $groups
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    private static function groupItems(array $groups, array $items): array
    {
        $grouped = [];

        foreach ($groups as $group) {
            $groupKey = (string) ($group['key'] ?? '');
            if ($groupKey === '') {
                continue;
            }

            $groupItems = [];
            foreach ($items as $item) {
                if ((string) ($item['group'] ?? '') !== $groupKey) {
                    continue;
                }

                $groupItems[] = [
                    'id' => (string) ($item['id'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                    'description' => (string) ($item['description'] ?? ''),
                    'checked' => (bool) ($item['defaultComplete'] ?? true),
                ];
            }

            $grouped[] = [
                'title' => (string) ($group['title'] ?? 'Blocos da tela'),
                'items' => $groupItems,
            ];
        }

        return $grouped;
    }

    /**
     * @return array<string,mixed>
     */
    private static function empty(string $pageKey): array
    {
        return [
            'pageKey' => $pageKey,
            'title' => 'Personalizar tela',
            'description' => 'Comece no modo essencial e habilite os blocos quando quiser.',
            'size' => '',
            'trigger' => [
                'id' => 'btnCustomizeDashboard',
                'wrapperClass' => 'lk-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'customizeModalOverlay',
                'title' => 'customizeModalTitle',
                'description' => 'customizeModalDescription',
                'close' => 'btnCloseCustomize',
                'save' => 'btnSaveCustomize',
                'presetEssential' => 'btnPresetEssencial',
                'presetComplete' => 'btnPresetCompleto',
            ],
            'items' => [],
            'sectionMap' => [],
            'gridToggleKeys' => [],
            'groups' => [],
        ];
    }
}
