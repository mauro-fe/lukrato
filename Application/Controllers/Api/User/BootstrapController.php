<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\ApiController;
use Application\Core\Response;

class BootstrapController extends ApiController
{
    public function show(): Response
    {
        $currentViewPath = $this->normalizeContextValue($this->getStringQuery('view_path'), true);
        $currentViewId = $this->normalizeContextValue($this->getStringQuery('view_id'));
        $menu = $this->normalizeContextValue($this->getStringQuery('menu'));

        if ($menu === '' && $currentViewPath !== '') {
            $menu = strtok($currentViewPath, '/') ?: '';
        }

        $layoutData = $this->injectAdminLayoutData([
            'currentUser' => $this->requireUser(),
            'menu' => $menu,
            'currentViewId' => $currentViewId,
            'currentViewPath' => $currentViewPath,
            'supportName' => '',
            'supportEmail' => '',
            'supportTel' => '',
            'supportDdd' => '',
        ]);

        return Response::successResponse($layoutData['adminRuntimeConfig'] ?? []);
    }

    private function normalizeContextValue(string $value, bool $allowSlash = false): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }

        $pattern = $allowSlash
            ? '/[^a-z0-9_\/-]+/'
            : '/[^a-z0-9_-]+/';

        $normalized = preg_replace($pattern, '', $value) ?? '';

        return trim($normalized, $allowSlash ? "/-" : '-');
    }
}
