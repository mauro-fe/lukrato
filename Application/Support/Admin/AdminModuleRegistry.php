<?php

declare(strict_types=1);

namespace Application\Support\Admin;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;

final class AdminModuleRegistry
{
    /**
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $modules = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        if (self::$modules !== null) {
            return self::$modules;
        }

        $modules = [];
        foreach (self::definitions() as $module) {
            $key = (string) ($module['key'] ?? '');
            if ($key === '') {
                continue;
            }

            if (array_key_exists($key, $modules)) {
                throw new \RuntimeException(
                    "Duplicate admin module key detected while loading module definitions: {$key}"
                );
            }

            $modules[$key] = self::normalizeModule($module);
        }

        self::$modules = $modules;

        return self::$modules;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function groupedSidebarModules(bool $isSysAdmin = false, bool $isPro = false): array
    {
        $result = [];
        $modules = self::modulesByPlacement('sidebar', $isSysAdmin, $isPro);

        foreach ($modules as $module) {
            $group = (string) ($module['group'] ?? '');
            if ($group === '') {
                continue;
            }

            if (!isset($result[$group])) {
                $result[$group] = [];
            }

            $result[$group][] = $module;
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function footerModules(bool $isSysAdmin = false, bool $isPro = false): array
    {
        return self::modulesByPlacement('footer', $isSysAdmin, $isPro);
    }

    public static function inferMenuFromViewPath(string $viewPath): ?string
    {
        $normalized = trim(str_replace('\\', '/', $viewPath), '/');
        if ($normalized === '' || !str_starts_with($normalized, 'admin/')) {
            return null;
        }

        $matchedMenu = null;
        $bestPrefixLength = -1;

        foreach (self::all() as $module) {
            if (($module['infer_menu'] ?? true) !== true) {
                continue;
            }

            $menu = $module['menu'] ?? null;
            if (!is_string($menu) || $menu === '') {
                continue;
            }

            $prefixes = $module['view_prefixes'] ?? [];
            if (!is_array($prefixes) || $prefixes === []) {
                continue;
            }

            foreach ($prefixes as $prefix) {
                if (!is_string($prefix) || $prefix === '') {
                    continue;
                }

                $normalizedPrefix = trim(str_replace('\\', '/', $prefix), '/');
                if ($normalizedPrefix === '') {
                    continue;
                }

                if (
                    $normalized !== $normalizedPrefix
                    && !str_starts_with($normalized, $normalizedPrefix . '/')
                ) {
                    continue;
                }

                $currentLength = strlen($normalizedPrefix);
                if ($currentLength > $bestPrefixLength) {
                    $bestPrefixLength = $currentLength;
                    $matchedMenu = $menu;
                }
            }
        }

        return $matchedMenu;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function resolveMenuByViewContext(string $viewPath, array $context = []): ?string
    {
        $normalizedView = trim(str_replace('\\', '/', $viewPath), '/');
        if ($normalizedView === '' || !str_starts_with($normalizedView, 'admin/')) {
            return null;
        }

        $matchedMenu = null;
        $bestPrefixLength = -1;
        $bestContextWeight = -1;

        foreach (self::all() as $module) {
            $menu = $module['menu'] ?? null;
            if (!is_string($menu) || $menu === '') {
                continue;
            }

            $contextRule = $module['menu_context'] ?? [];
            if (!is_array($contextRule) || $contextRule === []) {
                continue;
            }

            if (!self::contextMatches($contextRule, $context)) {
                continue;
            }

            $prefixLength = self::bestMatchingPrefixLength(
                $normalizedView,
                is_array($module['view_prefixes'] ?? null) ? $module['view_prefixes'] : []
            );
            if ($prefixLength < 0) {
                continue;
            }

            $contextWeight = count($contextRule);
            if ($contextWeight > $bestContextWeight || ($contextWeight === $bestContextWeight && $prefixLength > $bestPrefixLength)) {
                $bestContextWeight = $contextWeight;
                $bestPrefixLength = $prefixLength;
                $matchedMenu = $menu;
            }
        }

        if ($matchedMenu !== null) {
            return $matchedMenu;
        }

        return self::inferMenuFromViewPath($viewPath);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function resolvePageJsViewId(string $currentViewId, array $context = []): ?string
    {
        $currentViewId = trim($currentViewId);
        if ($currentViewId === '') {
            return null;
        }

        $resolvedViewId = null;
        $bestContextWeight = -1;

        foreach (self::all() as $module) {
            $viewIds = is_array($module['view_ids'] ?? null) ? $module['view_ids'] : [];
            $sourceViews = is_array($module['page_js_source_views'] ?? null)
                ? $module['page_js_source_views']
                : $viewIds;

            if ($sourceViews === [] || !in_array($currentViewId, $sourceViews, true)) {
                continue;
            }

            $targetViewId = self::firstViewId($viewIds);
            if ($targetViewId === null) {
                continue;
            }

            $contextRule = is_array($module['page_js_context'] ?? null) ? $module['page_js_context'] : [];
            if ($contextRule !== [] && !self::contextMatches($contextRule, $context)) {
                continue;
            }

            $contextWeight = count($contextRule);
            if ($contextWeight > $bestContextWeight) {
                $bestContextWeight = $contextWeight;
                $resolvedViewId = $targetViewId;
            }
        }

        return $resolvedViewId ?? $currentViewId;
    }

    public static function resolveViteEntryByViewId(string $viewId): ?string
    {
        $viewId = trim($viewId);
        if ($viewId === '') {
            return null;
        }

        foreach (self::all() as $module) {
            $viewIds = $module['view_ids'] ?? [];
            if (!is_array($viewIds) || !in_array($viewId, $viewIds, true)) {
                continue;
            }

            $entry = $module['vite_entry'] ?? null;
            if (is_string($entry) && $entry !== '') {
                return $entry;
            }
        }

        return null;
    }

    public static function resolveCssEntryByViewId(string $viewId): ?string
    {
        $viewId = trim($viewId);
        if ($viewId === '') {
            return null;
        }

        foreach (self::all() as $module) {
            $viewIds = $module['view_ids'] ?? [];
            if (!is_array($viewIds) || !in_array($viewId, $viewIds, true)) {
                continue;
            }

            $entry = $module['css_entry'] ?? null;
            if (is_string($entry) && $entry !== '') {
                return $entry;
            }

            $entries = $module['css_entries'] ?? [];
            if (is_array($entries)) {
                foreach ($entries as $candidate) {
                    if (is_string($candidate) && trim($candidate) !== '') {
                        return trim($candidate);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array{label: string, url?: string, icon?: string}>
     */
    public static function resolveBreadcrumbsByViewContext(string $viewPath, array $context = []): array
    {
        $normalizedView = trim(str_replace('\\', '/', $viewPath), '/');
        if ($normalizedView === '' || !str_starts_with($normalizedView, 'admin/')) {
            return [];
        }

        $matchedBreadcrumbs = [];
        $bestPrefixLength = -1;
        $bestContextWeight = -1;

        foreach (self::all() as $module) {
            $breadcrumbs = is_array($module['breadcrumbs'] ?? null) ? $module['breadcrumbs'] : [];
            if ($breadcrumbs === []) {
                continue;
            }

            $contextRule = is_array($module['breadcrumbs_context'] ?? null)
                ? $module['breadcrumbs_context']
                : [];
            if ($contextRule !== [] && !self::contextMatches($contextRule, $context)) {
                continue;
            }

            $prefixLength = self::bestMatchingPrefixLength(
                $normalizedView,
                is_array($module['view_prefixes'] ?? null) ? $module['view_prefixes'] : []
            );
            if ($prefixLength < 0) {
                continue;
            }

            $contextWeight = count($contextRule);
            if ($contextWeight > $bestContextWeight || ($contextWeight === $bestContextWeight && $prefixLength > $bestPrefixLength)) {
                $bestContextWeight = $contextWeight;
                $bestPrefixLength = $prefixLength;
                $matchedBreadcrumbs = $breadcrumbs;
            }
        }

        return $matchedBreadcrumbs;
    }

    /**
     * @param string $placement
     * @return array<int, array<string, mixed>>
     */
    private static function modulesByPlacement(string $placement, bool $isSysAdmin, bool $isPro): array
    {
        $modules = [];

        foreach (self::all() as $module) {
            if (($module['placement'] ?? '') !== $placement) {
                continue;
            }

            if (($module['hidden'] ?? false) === true) {
                continue;
            }

            if (($module['sysadmin_only'] ?? false) === true && !$isSysAdmin) {
                continue;
            }

            if (($module['pro_only'] ?? false) === true && !$isPro) {
                continue;
            }

            $modules[] = $module;
        }

        usort(
            $modules,
            static fn(array $left, array $right): int => (int) ($left['order'] ?? 9999) <=> (int) ($right['order'] ?? 9999)
        );

        return $modules;
    }

    /**
     * @param array<string, mixed> $module
     * @return array<string, mixed>
     */
    private static function normalizeModule(array $module): array
    {
        $module['label'] = (string) ($module['label'] ?? $module['key'] ?? '');
        $module['title'] = (string) ($module['title'] ?? $module['label']);
        $module['icon'] = (string) ($module['icon'] ?? 'circle');
        $module['group'] = (string) ($module['group'] ?? '');
        $module['route'] = (string) ($module['route'] ?? '');
        $module['menu'] = $module['menu'] ?? null;
        $module['view_prefix'] = $module['view_prefix'] ?? '';
        $module['view_prefixes'] = self::normalizeStringList(
            $module['view_prefixes'] ?? [$module['view_prefix']]
        );
        $module['view_ids'] = self::normalizeStringList($module['view_ids'] ?? []);
        $module['page_js_source_views'] = self::normalizeStringList(
            $module['page_js_source_views'] ?? $module['view_ids']
        );
        $module['vite_entry'] = (string) ($module['vite_entry'] ?? '');
        $module['css_entry'] = (string) ($module['css_entry'] ?? '');
        $module['css_entries'] = self::normalizeStringList(
            $module['css_entries'] ?? [$module['css_entry']]
        );
        $module['breadcrumbs'] = self::normalizeBreadcrumbs($module['breadcrumbs'] ?? []);
        $module['breadcrumbs_context'] = self::normalizeContextRule($module['breadcrumbs_context'] ?? []);
        $module['menu_context'] = self::normalizeContextRule($module['menu_context'] ?? []);
        $module['page_js_context'] = self::normalizeContextRule($module['page_js_context'] ?? []);
        $module['placement'] = (string) ($module['placement'] ?? 'hidden');
        $module['order'] = (int) ($module['order'] ?? 9999);
        $module['hidden'] = (bool) ($module['hidden'] ?? false);
        $module['sysadmin_only'] = (bool) ($module['sysadmin_only'] ?? false);
        $module['pro_only'] = (bool) ($module['pro_only'] ?? false);
        $module['infer_menu'] = (bool) ($module['infer_menu'] ?? true);

        return $module;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }

            $trimmed = trim($item);
            if ($trimmed !== '') {
                $items[] = $trimmed;
            }
        }

        return $items;
    }

    /**
     * @param mixed $value
     * @return array<string, scalar|null>
     */
    private static function normalizeContextRule(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $rule = [];
        foreach ($value as $key => $expected) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }

            if (
                $expected === null
                || is_string($expected)
                || is_int($expected)
                || is_float($expected)
                || is_bool($expected)
            ) {
                $rule[trim($key)] = $expected;
            }
        }

        return $rule;
    }

    /**
     * @param mixed $value
     * @return array<int, array{label: string, url?: string, icon?: string}>
     */
    private static function normalizeBreadcrumbs(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $crumb) {
            if (!is_array($crumb)) {
                continue;
            }

            $label = trim((string) ($crumb['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $item = ['label' => $label];

            $url = trim((string) ($crumb['url'] ?? ''));
            if ($url !== '') {
                $item['url'] = $url;
            }

            $icon = trim((string) ($crumb['icon'] ?? ''));
            if ($icon !== '') {
                $item['icon'] = $icon;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param array<string, scalar|null> $rule
     * @param array<string, mixed> $context
     */
    private static function contextMatches(array $rule, array $context): bool
    {
        foreach ($rule as $key => $expectedValue) {
            if (!array_key_exists($key, $context)) {
                return false;
            }

            if ($context[$key] !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $prefixes
     */
    private static function bestMatchingPrefixLength(string $viewPath, array $prefixes): int
    {
        $bestLength = -1;

        foreach ($prefixes as $prefix) {
            $normalizedPrefix = trim(str_replace('\\', '/', $prefix), '/');
            if ($normalizedPrefix === '') {
                continue;
            }

            if ($viewPath !== $normalizedPrefix && !str_starts_with($viewPath, $normalizedPrefix . '/')) {
                continue;
            }

            $bestLength = max($bestLength, strlen($normalizedPrefix));
        }

        return $bestLength;
    }

    /**
     * @param array<int, string> $viewIds
     */
    private static function firstViewId(array $viewIds): ?string
    {
        foreach ($viewIds as $viewId) {
            if (is_string($viewId) && trim($viewId) !== '') {
                return trim($viewId);
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function definitions(): array
    {
        $modulesPath = self::modulesBasePath();
        if (!is_dir($modulesPath)) {
            return [];
        }

        $files = glob($modulesPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'module.php') ?: [];
        sort($files, SORT_STRING);

        $definitions = [];

        foreach ($files as $file) {
            $loaded = require $file;

            if (!is_array($loaded)) {
                throw new \RuntimeException(
                    "Invalid admin module file payload in {$file}. Expected array."
                );
            }

            $items = array_is_list($loaded) ? $loaded : [$loaded];

            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    throw new \RuntimeException(
                        "Invalid admin module definition in {$file} at index {$index}. Expected array."
                    );
                }

                $definitions[] = $item;
            }
        }

        return $definitions;
    }

    private static function modulesBasePath(): string
    {
        return self::runtimeConfig()->adminModulesBasePath();
    }

    private static function runtimeConfig(): InfrastructureRuntimeConfig
    {
        return ApplicationContainer::tryMake(InfrastructureRuntimeConfig::class)
            ?? new InfrastructureRuntimeConfig();
    }
}
