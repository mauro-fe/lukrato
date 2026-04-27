<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class LancamentoController extends WebController
{
    public function index(): Response
    {
        $user = $this->requireUser();

        return $this->renderAdminResponse(
            'admin/lancamentos/index',
            [
                'pageTitle' => 'Transações',
                'subTitle' => 'Gerencie suas transações financeiras',
                'isPro' => $user->isPro(),
                'showMonthSelector' => true,
            ]
        );
    }

    public function create(): Response
    {
        $user = $this->requireUser();

        $returnPath = $this->normalizeReturnPath($this->getStringQuery('return', ''));
        $backPath = $returnPath !== '' ? $returnPath : 'lancamentos';
        $backUrl = $this->buildAppUrl($backPath);

        return $this->renderAdminResponse(
            'admin/lancamentos/create',
            [
                'pageTitle' => 'Nova Transação',
                'subTitle' => 'Registre receitas, despesas e transferências em uma tela dedicada',
                'isPro' => $user->isPro(),
                'menu' => 'lancamentos',
                'hideLaunchFab' => true,
                'hideSupportFab' => true,
                'hideScrollToTop' => true,
                'backUrl' => $backUrl,
                'backLabel' => $this->resolveBackLabel($backPath),
                'wizardSource' => $this->normalizeWizardSource($this->getStringQuery('origem', '')),
                'wizardPresetAccountId' => $this->normalizePositiveInt($this->getQuery('conta')),
                'wizardTipo' => $this->normalizeWizardTipo($this->getStringQuery('tipo', '')),
            ]
        );
    }

    private function normalizeWizardSource(string $rawSource): string
    {
        return strtolower(trim($rawSource)) === 'contas' ? 'contas' : 'global';
    }

    private function normalizeWizardTipo(string $rawTipo): ?string
    {
        $tipo = strtolower(trim($rawTipo));

        return in_array($tipo, ['receita', 'despesa', 'transferencia'], true)
            ? $tipo
            : null;
    }

    private function normalizePositiveInt(mixed $value): ?int
    {
        if (is_numeric($value)) {
            $normalized = (int) $value;

            return $normalized > 0 ? $normalized : null;
        }

        return null;
    }

    private function buildAppUrl(string $path): string
    {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    private function resolveBackLabel(string $backPath): string
    {
        $pathOnly = strtolower(trim((string) parse_url($backPath, PHP_URL_PATH), '/'));

        return match (true) {
            $pathOnly === 'dashboard' => 'Voltar para dashboard',
            str_starts_with($pathOnly, 'contas') => 'Voltar para contas',
            str_starts_with($pathOnly, 'faturas') => 'Voltar para faturas',
            str_starts_with($pathOnly, 'relatorios') => 'Voltar para relatórios',
            default => 'Voltar para transações',
        };
    }

    private function normalizeReturnPath(string $rawReturnPath): string
    {
        $rawReturnPath = trim($rawReturnPath);
        if ($rawReturnPath === '') {
            return '';
        }

        if (preg_match('#^(?:https?:)?//#i', $rawReturnPath) === 1) {
            return '';
        }

        $parts = parse_url($rawReturnPath);
        if (!is_array($parts)) {
            return '';
        }

        $path = trim((string) ($parts['path'] ?? ''), '/ ');
        if ($path === '' || !preg_match('#^[a-zA-Z0-9/_\-]+$#', $path)) {
            return '';
        }

        if ($path === 'lancamentos/novo' || str_starts_with($path, 'lancamentos/novo/')) {
            return '';
        }

        $queryString = '';
        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $queryParams);

            $filteredParams = [];
            foreach ($queryParams as $key => $value) {
                if (!is_string($key) || !preg_match('#^[a-zA-Z0-9_.\-]+$#', $key)) {
                    continue;
                }

                if (is_scalar($value) || $value === null) {
                    $normalizedValue = trim((string) $value);
                    if ($normalizedValue !== '') {
                        $filteredParams[$key] = $normalizedValue;
                    }
                }
            }

            if ($filteredParams !== []) {
                $queryString = '?' . http_build_query($filteredParams);
            }
        }

        return $path . $queryString;
    }
}
