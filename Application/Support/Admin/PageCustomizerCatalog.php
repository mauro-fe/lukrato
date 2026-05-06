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
            'billing' => self::billing(),
            'categorias' => self::categorias(),
            'dashboard' => self::dashboard(),
            'cartoes' => self::cartoes(),
            'contas' => self::contas(),
            'faturas' => self::faturas(),
            'financas' => self::financas(),
            'gamification' => self::gamification(),
            'importacoes' => self::importacoes(),
            'lancamentos' => self::lancamentos(),
            'metas' => self::metas(),
            'orcamento' => self::orcamento(),
            'perfil' => self::perfil(),
            'relatorios' => self::relatorios(),
            'sysadmin' => self::sysadmin(),
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
                'plan' => 'free',
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
                'plan' => 'pro',
                'label' => 'Saúde financeira',
                'description' => 'Resumo rápido do momento atual das suas finanças.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleAiTip',
                'sectionId' => 'sectionAiTip',
                'group' => 'primary',
                'plan' => 'pro',
                'label' => 'Dicas do Lukrato',
                'description' => 'Insights e recomendações geradas com base no seu cenário.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleEvolucao',
                'sectionId' => 'sectionEvolucao',
                'group' => 'primary',
                'plan' => 'pro',
                'label' => 'Evolução financeira',
                'description' => 'Acompanhe a tendência da sua performance ao longo do tempo.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'togglePrevisao',
                'sectionId' => 'sectionPrevisao',
                'group' => 'primary',
                'plan' => 'pro',
                'label' => 'Previsão financeira',
                'description' => 'Veja o impacto projetado das próximas movimentações.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleGrafico',
                'sectionId' => 'chart-section',
                'group' => 'primary',
                'plan' => 'free',
                'label' => 'Gráfico de categorias',
                'description' => 'Distribuição visual dos seus gastos por categoria.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleMetas',
                'sectionId' => 'sectionMetas',
                'group' => 'extras',
                'plan' => 'pro',
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
                'plan' => 'pro',
                'label' => 'Cartões',
                'description' => 'Limites, uso atual e visão rápida dos seus cartões.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleContas',
                'sectionId' => 'sectionContas',
                'group' => 'extras',
                'plan' => 'pro',
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
                'plan' => 'pro',
                'label' => 'Orçamentos',
                'description' => 'Controle visual do que já consumiu em cada orçamento.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleFaturas',
                'sectionId' => 'sectionFaturas',
                'group' => 'extras',
                'plan' => 'pro',
                'label' => 'Faturas de cartão',
                'description' => 'Próximos fechamentos e situação das faturas abertas.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => true,
            ],
            [
                'id' => 'toggleGamificacao',
                'sectionId' => 'sectionGamificacao',
                'group' => 'extras',
                'plan' => 'pro',
                'label' => 'Gamificação',
                'description' => 'Nível, progresso e incentivos para manter sua rotina.',
                'defaultEssential' => false,
                'defaultComplete' => false,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'dashboard',
            'title' => 'Personalizar dashboard',
            'description' => 'Comece no modo essencial e ative extras quando fizer sentido para você.',
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
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie o dashboard completo no Pro',
                'description' => 'Você está no modo essencial. No Pro, libera personalização e blocos extras. No Ultra, entram os insights automáticos.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, presets e blocos extras.',
                    ],
                    [
                        'name' => 'Ultra',
                        'description' => 'Adiciona inteligência e insights automáticos.',
                    ],
                ],
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
     * @return array<string,mixed>
     */
    private static function contas(): array
    {
        $items = [
            [
                'id' => 'toggleContasHero',
                'sectionId' => 'contasHero',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Visão consolidada',
                'description' => 'Mantém o saldo total e o resumo principal logo no topo da página.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleContasKpis',
                'sectionId' => 'contasKpis',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Cards de KPI',
                'description' => 'Mostra os destaques de conta principal e reserva no topo executivo.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'contas',
            'title' => 'Personalizar contas',
            'description' => 'Escolha entre uma visão essencial das contas ou o topo completo com KPIs.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeContas',
                'wrapperClass' => 'cont-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'contasCustomizeModalOverlay',
                'title' => 'contasCustomizeModalTitle',
                'description' => 'contasCustomizeModalDescription',
                'close' => 'btnCloseCustomizeContas',
                'save' => 'btnSaveCustomizeContas',
                'presetEssential' => 'btnPresetEssencialContas',
                'presetComplete' => 'btnPresetCompletoContas',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie a visão completa de contas no Pro',
                'description' => 'No essencial você continua com a visão consolidada das contas. No Pro, libera os cards de KPI e a personalização da página.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização e os KPIs de conta principal e reserva.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function cartoes(): array
    {
        $items = [
            [
                'id' => 'toggleCartoesKpis',
                'sectionId' => 'cartoesKpis',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Resumo consolidado',
                'description' => 'Mostra o topo com limite total, quantidade de cartões e uso consolidado.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleCartoesToolbar',
                'sectionId' => 'cartoesToolbar',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Barra de filtros',
                'description' => 'Libera busca rápida e atalhos por bandeira direto na listagem.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'cartoes',
            'title' => 'Personalizar cartões',
            'description' => 'Escolha entre a lista essencial de cartões ou a tela completa com resumo e filtros.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeCartoes',
                'wrapperClass' => 'cart-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'cartoesCustomizeModalOverlay',
                'title' => 'cartoesCustomizeModalTitle',
                'description' => 'cartoesCustomizeModalDescription',
                'close' => 'btnCloseCustomizeCartoes',
                'save' => 'btnSaveCustomizeCartoes',
                'presetEssential' => 'btnPresetEssencialCartoes',
                'presetComplete' => 'btnPresetCompletoCartoes',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie a visão completa de cartões no Pro',
                'description' => 'No essencial você continua com a lista e os alertas. No Pro, libera o resumo consolidado, a barra de filtros e a personalização da página.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, resumo consolidado e filtros rápidos.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function faturas(): array
    {
        $items = [
            [
                'id' => 'toggleFaturasHero',
                'sectionId' => 'faturasHero',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Cabeçalho principal',
                'description' => 'Mantém o contexto da página e os atalhos rápidos de importação.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleFaturasFiltros',
                'sectionId' => 'faturasFilters',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Painel de filtros',
                'description' => 'Libera o bloco completo de filtros para refinar a leitura das faturas.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleFaturasViewToggle',
                'sectionId' => 'faturasViewToggle',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Toggle de visualização',
                'description' => 'Permite alternar entre cards e lista na própria página.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'faturas',
            'title' => 'Personalizar faturas',
            'description' => 'Mantenha uma leitura essencial das faturas ou libere a navegação completa com filtros.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeFaturas',
                'wrapperClass' => 'fat-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'faturasCustomizeModalOverlay',
                'title' => 'faturasCustomizeModalTitle',
                'description' => 'faturasCustomizeModalDescription',
                'close' => 'btnCloseCustomizeFaturas',
                'save' => 'btnSaveCustomizeFaturas',
                'presetEssential' => 'btnPresetEssencialFaturas',
                'presetComplete' => 'btnPresetCompletoFaturas',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie as faturas completas no Pro',
                'description' => 'No essencial você segue com a leitura principal das faturas. No Pro, libera filtros, personalização e alternância de visualização.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, filtros completos e toggle de visualização.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function importacoes(): array
    {
        $items = [
            [
                'id' => 'toggleImpHero',
                'sectionId' => 'impHeroSection',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Cabeçalho de contexto',
                'description' => 'Mostra o resumo introdutório do fluxo de importação no topo do card principal.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleImpSidebar',
                'sectionId' => 'impIndexSideSection',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Painel lateral de apoio',
                'description' => 'Libera o sidebar com resumo de plano, perfil CSV e histórico recente.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'importacoes',
            'title' => 'Personalizar importações',
            'description' => 'Mantenha o fluxo focado no envio do arquivo ou libere o contexto completo da tela.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeImportacoes',
                'wrapperClass' => 'imp-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'importacoesCustomizeModalOverlay',
                'title' => 'importacoesCustomizeModalTitle',
                'description' => 'importacoesCustomizeModalDescription',
                'close' => 'btnCloseCustomizeImportacoes',
                'save' => 'btnSaveCustomizeImportacoes',
                'presetEssential' => 'btnPresetEssencialImportacoes',
                'presetComplete' => 'btnPresetCompletoImportacoes',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie as importações completas no Pro',
                'description' => 'No essencial a tela fica focada no envio e revisão do arquivo. No Pro, libera o contexto completo com cabeçalho e painel lateral.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, cabeçalho contextual e painel lateral de apoio.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function lancamentos(): array
    {
        $items = [
            [
                'id' => 'toggleLanFilters',
                'sectionId' => 'lanFiltersSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Filtros e busca',
                'description' => 'Mantém a barra operacional para localizar e refinar transações rapidamente.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleLanExport',
                'sectionId' => 'exportCard',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Área de exportação',
                'description' => 'Libera exportação em PDF e Excel com filtros próprios.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'lancamentos',
            'title' => 'Personalizar transações',
            'description' => 'Escolha entre um modo essencial para operar rápido ou a tela completa com exportação.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeLancamentos',
                'wrapperClass' => 'lan-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'lanCustomizeModalOverlay',
                'title' => 'lanCustomizeModalTitle',
                'description' => 'lanCustomizeModalDescription',
                'close' => 'btnCloseCustomizeLancamentos',
                'save' => 'btnSaveCustomizeLancamentos',
                'presetEssential' => 'btnPresetEssencialLancamentos',
                'presetComplete' => 'btnPresetCompletoLancamentos',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie as transações completas no Pro',
                'description' => 'No essencial você continua com a listagem e os filtros principais. No Pro, libera personalização da página e exportação completa dos lançamentos.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização da tela e exportação em PDF ou Excel.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function metas(): array
    {
        $items = [
            [
                'id' => 'toggleMetasSummary',
                'sectionId' => 'summaryMetas',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Resumo de metas',
                'description' => 'Mostra os cards com volume total, progresso e visão consolidada das metas.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleMetasFocus',
                'sectionId' => 'metFocusPanel',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Foco do momento',
                'description' => 'Exibe o bloco lateral com o próximo passo e os principais alertas das metas.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleMetasToolbar',
                'sectionId' => 'metToolbarSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Barra de filtros',
                'description' => 'Mantém busca, filtros e ordenação para operar a lista de metas.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'metas',
            'title' => 'Personalizar metas',
            'description' => 'Comece com a leitura essencial e libere o topo executivo das metas quando precisar.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeMetas',
                'wrapperClass' => 'met-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'metasCustomizeModalOverlay',
                'title' => 'metasCustomizeModalTitle',
                'description' => 'metasCustomizeModalDescription',
                'close' => 'btnCloseCustomizeMetas',
                'save' => 'btnSaveCustomizeMetas',
                'presetEssential' => 'btnPresetEssencialMetas',
                'presetComplete' => 'btnPresetCompletoMetas',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie as metas completas no Pro',
                'description' => 'No essencial você continua com a lista operável das metas. No Pro, libera resumo, foco lateral e personalização da página.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, resumo executivo e bloco de foco do momento.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function orcamento(): array
    {
        $items = [
            [
                'id' => 'toggleOrcSummary',
                'sectionId' => 'summaryOrcamentos',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Cards de resumo',
                'description' => 'Mostra o topo com orçado, gasto e disponível do ciclo.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleOrcFocus',
                'sectionId' => 'orcFocusPanel',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Foco do período',
                'description' => 'Exibe o painel lateral com prioridades e estatísticas do orçamento.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleOrcToolbar',
                'sectionId' => 'orcToolbarSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Barra de filtros',
                'description' => 'Mantém busca, chips e ordenação para operar os orçamentos.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'orcamento',
            'title' => 'Personalizar orçamento',
            'description' => 'Comece no modo essencial e libere os blocos de apoio quando fizer sentido.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeOrcamento',
                'wrapperClass' => 'orc-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'orcamentoCustomizeModalOverlay',
                'title' => 'orcamentoCustomizeModalTitle',
                'description' => 'orcamentoCustomizeModalDescription',
                'close' => 'btnCloseCustomizeOrcamento',
                'save' => 'btnSaveCustomizeOrcamento',
                'presetEssential' => 'btnPresetEssencialOrcamento',
                'presetComplete' => 'btnPresetCompletoOrcamento',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie o orçamento completo no Pro',
                'description' => 'No essencial você continua com a leitura operacional da página. No Pro, libera resumo executivo, foco lateral e personalização da tela.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, resumo executivo e foco do período.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function billing(): array
    {
        $items = [
            [
                'id' => 'toggleBillingHeader',
                'sectionId' => 'billingHeaderSection',
                'group' => 'layout',
                'plan' => 'free',
                'label' => 'Cabeçalho da página',
                'description' => 'Mantém o título e o contexto principal da área de assinatura.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleBillingPlans',
                'sectionId' => 'billingPlansSection',
                'group' => 'layout',
                'plan' => 'free',
                'label' => 'Grid de planos',
                'description' => 'Controla a vitrine dos planos e seus CTAs.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'billing',
            'title' => 'Personalizar assinatura',
            'description' => 'Escolha quais blocos quer manter visíveis na página de planos.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeBilling',
                'wrapperClass' => 'bill-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'billingCustomizeModalOverlay',
                'title' => 'billingCustomizeModalTitle',
                'description' => 'billingCustomizeModalDescription',
                'close' => 'btnCloseCustomizeBilling',
                'save' => 'btnSaveCustomizeBilling',
                'presetEssential' => 'btnPresetEssencialBilling',
                'presetComplete' => 'btnPresetCompletoBilling',
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'layout', 'title' => 'Layout'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function categorias(): array
    {
        $items = [
            [
                'id' => 'toggleCategoriasKpis',
                'sectionId' => 'categoriasKpis',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Cards de KPI',
                'description' => 'Exibe o topo com totais de categorias, subcategorias e orçamento.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleCategoriasCreateCard',
                'sectionId' => 'categoriasCreateCard',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Card de criação',
                'description' => 'Mantém o fluxo rápido para criar novas categorias na própria tela.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'categorias',
            'title' => 'Personalizar categorias',
            'description' => 'Comece com a grade principal e libere os KPIs quando quiser uma visão mais executiva.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeCategorias',
                'wrapperClass' => 'cat-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'categoriasCustomizeModalOverlay',
                'title' => 'categoriasCustomizeModalTitle',
                'description' => 'categoriasCustomizeModalDescription',
                'close' => 'btnCloseCustomizeCategorias',
                'save' => 'btnSaveCustomizeCategorias',
                'presetEssential' => 'btnPresetEssencialCategorias',
                'presetComplete' => 'btnPresetCompletoCategorias',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie as categorias completas no Pro',
                'description' => 'No essencial você continua criando e organizando categorias. No Pro, libera os KPIs e a personalização da página.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização e o topo com KPIs da sua estrutura de categorias.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function financas(): array
    {
        $items = [
            [
                'id' => 'toggleFinSummary',
                'sectionId' => 'finSummarySection',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Cards de resumo',
                'description' => 'Mostra o topo consolidado de orçamentos e metas.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleFinOrcActions',
                'sectionId' => 'finOrcActionsSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Ações da aba orçamentos',
                'description' => 'Mantém os atalhos operacionais para criar e sugerir orçamentos.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleFinMetasActions',
                'sectionId' => 'finMetasActionsSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Ações da aba metas',
                'description' => 'Mantém os atalhos para criar metas e usar templates.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleFinInsights',
                'sectionId' => 'insightsSection',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Insights de orçamentos',
                'description' => 'Libera o bloco de insights contextuais dentro da aba de orçamentos.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'financas',
            'title' => 'Personalizar finanças',
            'description' => 'Mantenha a operação essencial das abas ou libere o topo completo com resumos e insights.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeFinancas',
                'wrapperClass' => 'fin-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'financasCustomizeModalOverlay',
                'title' => 'financasCustomizeModalTitle',
                'description' => 'financasCustomizeModalDescription',
                'close' => 'btnCloseCustomizeFinancas',
                'save' => 'btnSaveCustomizeFinancas',
                'presetEssential' => 'btnPresetEssencialFinancas',
                'presetComplete' => 'btnPresetCompletoFinancas',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie as finanças completas no Pro',
                'description' => 'No essencial você segue operando orçamentos e metas. No Pro, libera o resumo consolidado, insights e a personalização da página.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, resumo consolidado e insights na aba de orçamentos.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function gamification(): array
    {
        $items = [
            [
                'id' => 'toggleGamHeader',
                'sectionId' => 'gamHeaderSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Cabeçalho',
                'description' => 'Mantém o topo com seu nível atual e contexto da jornada.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleGamProgress',
                'sectionId' => 'gamProgressSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Progresso geral',
                'description' => 'Mostra os cards de progresso e a barra para o próximo nível.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleGamAchievements',
                'sectionId' => 'gamAchievementsSection',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Conquistas',
                'description' => 'Mantém a vitrine principal das conquistas desbloqueadas e pendentes.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleGamHistory',
                'sectionId' => 'gamHistorySection',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Histórico recente',
                'description' => 'Libera a leitura do histórico mais recente de pontos e ações.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleGamLeaderboard',
                'sectionId' => 'gamLeaderboardSection',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Ranking',
                'description' => 'Exibe o bloco de ranking na leitura completa da jornada.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'gamification',
            'title' => 'Personalizar gamificação',
            'description' => 'Mantenha a jornada essencial em foco ou libere o histórico e o ranking na tela completa.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeGamification',
                'wrapperClass' => 'gam-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'gamificationCustomizeModalOverlay',
                'title' => 'gamificationCustomizeModalTitle',
                'description' => 'gamificationCustomizeModalDescription',
                'close' => 'btnCloseCustomizeGamification',
                'save' => 'btnSaveCustomizeGamification',
                'presetEssential' => 'btnPresetEssencialGamification',
                'presetComplete' => 'btnPresetCompletoGamification',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie a gamificação completa no Pro',
                'description' => 'No essencial você acompanha nível, progresso e conquistas. No Pro, libera histórico recente, ranking e personalização da página.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera personalização, histórico recente e ranking completo.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function perfil(): array
    {
        $items = [
            [
                'id' => 'togglePerfilHeader',
                'sectionId' => 'profileHeaderSection',
                'group' => 'layout',
                'plan' => 'free',
                'label' => 'Cabeçalho do perfil',
                'description' => 'Mantém o bloco superior com avatar, contexto e destaques do perfil.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'togglePerfilTabs',
                'sectionId' => 'profileTabsSection',
                'group' => 'layout',
                'plan' => 'free',
                'label' => 'Navegação por abas',
                'description' => 'Controla a navegação entre os painéis de dados pessoais e endereço.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'perfil',
            'title' => 'Personalizar perfil',
            'description' => 'Escolha quais blocos estruturais quer manter visíveis na tela de perfil.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizePerfil',
                'wrapperClass' => 'profile-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'perfilCustomizeModalOverlay',
                'title' => 'perfilCustomizeModalTitle',
                'description' => 'perfilCustomizeModalDescription',
                'close' => 'btnCloseCustomizePerfil',
                'save' => 'btnSaveCustomizePerfil',
                'presetEssential' => 'btnPresetEssencialPerfil',
                'presetComplete' => 'btnPresetCompletoPerfil',
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'layout', 'title' => 'Layout'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function relatorios(): array
    {
        $items = [
            [
                'id' => 'toggleRelSectionInsights',
                'sectionId' => 'section-insights',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Área dedicada de insights',
                'description' => 'Abre a seção própria de insights inteligentes na navegação.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleRelSectionRelatorios',
                'sectionId' => 'section-relatorios',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Relatórios completos',
                'description' => 'Análises por categoria, conta, cartão e evolução.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleRelControls',
                'sectionId' => 'relControlsRow',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Barra de filtros e ações',
                'description' => 'Mostra filtros rápidos, limpeza e exportação.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleRelSectionComparativos',
                'sectionId' => 'section-comparativos',
                'group' => 'advanced',
                'plan' => 'pro',
                'label' => 'Comparativos',
                'description' => 'Compare períodos, ritmo e eficiência financeira.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'relatorios',
            'title' => 'Personalizar relatórios',
            'description' => 'Escolha entre um modo essencial, mais direto, ou a leitura completa da página.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeRelatorios',
                'wrapperClass' => 'rel-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'relatoriosCustomizeModalOverlay',
                'title' => 'relatoriosCustomizeModalTitle',
                'description' => 'relatoriosCustomizeModalDescription',
                'close' => 'btnCloseCustomizeRelatorios',
                'save' => 'btnSaveCustomizeRelatorios',
                'presetEssential' => 'btnPresetEssencialRelatorios',
                'presetComplete' => 'btnPresetCompletoRelatorios',
            ],
            'lockedState' => [
                'eyebrow' => 'Modo essencial ativo',
                'title' => 'Desbloqueie os relatórios completos no Pro',
                'description' => 'No essencial você fica só com a Visão Geral completa. No Pro, libera a navegação completa, filtros, comparativos e exportação. No Ultra, entra a camada inteligente com leituras automáticas.',
                'tiers' => [
                    [
                        'name' => 'Pro',
                        'description' => 'Libera relatórios completos, insights dedicados, filtros, comparativos e exportação.',
                    ],
                    [
                        'name' => 'Ultra',
                        'description' => 'Adiciona insights automáticos e leitura financeira mais inteligente.',
                    ],
                ],
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'advanced', 'title' => 'Completo'],
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function sysadmin(): array
    {
        $items = [
            [
                'id' => 'toggleSysStats',
                'sectionId' => 'sysStatsGrid',
                'group' => 'advanced',
                'plan' => 'free',
                'label' => 'Cards de status',
                'description' => 'Mostra o grid inicial com métricas rápidas do painel administrativo.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleSysTabs',
                'sectionId' => 'sysTabsNav',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Menu de abas',
                'description' => 'Mantém a navegação principal entre as áreas do painel.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleSysDashboard',
                'sectionId' => 'panel-dashboard',
                'group' => 'essential',
                'plan' => 'free',
                'label' => 'Painel visão geral',
                'description' => 'Mostra a aba principal com métricas e gráficos do sistema.',
                'defaultEssential' => true,
                'defaultComplete' => true,
                'grid' => false,
            ],
            [
                'id' => 'toggleSysFeedback',
                'sectionId' => 'panel-feedback',
                'group' => 'advanced',
                'plan' => 'free',
                'label' => 'Painel feedback',
                'description' => 'Inclui a aba dedicada ao acompanhamento do feedback dos usuários.',
                'defaultEssential' => false,
                'defaultComplete' => true,
                'grid' => false,
            ],
        ];

        return [
            'pageKey' => 'sysadmin',
            'title' => 'Personalizar sysadmin',
            'description' => 'Ajuste o painel administrativo entre uma leitura essencial e a visão completa.',
            'size' => 'wide',
            'trigger' => [
                'id' => 'btnCustomizeSysadmin',
                'wrapperClass' => 'sys-customize-trigger',
            ],
            'ids' => [
                'overlay' => 'sysadminCustomizeModalOverlay',
                'title' => 'sysadminCustomizeModalTitle',
                'description' => 'sysadminCustomizeModalDescription',
                'close' => 'btnCloseCustomizeSysadmin',
                'save' => 'btnSaveCustomizeSysadmin',
                'presetEssential' => 'btnPresetEssencialSysadmin',
                'presetComplete' => 'btnPresetCompletoSysadmin',
            ],
            'items' => $items,
            'sectionMap' => self::sectionMapFromItems($items),
            'gridToggleKeys' => self::gridToggleKeysFromItems($items),
            'groups' => self::groupItems(
                [
                    ['key' => 'essential', 'title' => 'Essencial'],
                    ['key' => 'advanced', 'title' => 'Completo'],
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
            $groupKey = (string) $group['key'];
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
                    'plan' => (string) ($item['plan'] ?? 'free'),
                    'checked' => (bool) ($item['defaultComplete'] ?? true),
                ];
            }

            $grouped[] = [
                'title' => (string) $group['title'],
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
