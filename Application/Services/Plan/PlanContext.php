<?php

declare(strict_types=1);

namespace Application\Services\Plan;

use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;

/**
 * Superfície unificada de leitura do plano de um usuário.
 * Usa Billing.php como fonte de verdade para tier, features e limites.
 */
final class PlanContext
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    private ?string $resolvedTier = null;

    public function __construct(private readonly Usuario $user) {}

    public static function forUser(Usuario $user): self
    {
        return new self($user);
    }

    /** @return array<string, mixed> */
    public static function config(): array
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../Config/Billing.php';
        }

        return self::$config;
    }

    public static function message(string $key, array $vars = []): string
    {
        $msg = (string) (self::config()['messages'][$key] ?? '');

        foreach ($vars as $varKey => $value) {
            $msg = str_replace("{{$varKey}}", (string) $value, $msg);
        }

        return $msg;
    }

    /** @return array<string, array{rank:int,label:string,upgrade_target:?string}> */
    private static function tiers(): array
    {
        $tiers = self::config()['tiers'] ?? null;

        if (is_array($tiers) && $tiers !== []) {
            /** @var array<string, array{rank:int,label:string,upgrade_target:?string}> $tiers */
            return $tiers;
        }

        return [
            'free' => ['rank' => 0, 'label' => 'FREE', 'upgrade_target' => 'pro'],
            'pro' => ['rank' => 1, 'label' => 'PRO', 'upgrade_target' => 'ultra'],
            'ultra' => ['rank' => 2, 'label' => 'ULTRA', 'upgrade_target' => null],
        ];
    }

    /**
     * @return array<string, bool|string|null>
     */
    public static function summaryForTier(string $tier, string $tierKey = 'plan'): array
    {
        $normalizedTier = self::normalizeTier($tier);
        $tiers = self::tiers();
        $rank = (int) ($tiers[$normalizedTier]['rank'] ?? 0);

        return [
            $tierKey => $normalizedTier,
            'is_pro' => $rank >= (int) ($tiers['pro']['rank'] ?? 1),
            'is_ultra' => $normalizedTier === 'ultra',
            'plan_label' => (string) ($tiers[$normalizedTier]['label'] ?? strtoupper($normalizedTier)),
            'upgrade_target' => $tiers[$normalizedTier]['upgrade_target'] ?? null,
        ];
    }

    public function tier(): string
    {
        if ($this->resolvedTier !== null) {
            return $this->resolvedTier;
        }

        $code = strtolower((string) ($this->user->planoAtual()->code ?? ''));
        $hasPaidAccess = $this->user->isPro();

        if (!$hasPaidAccess) {
            if ($code !== '' && $code !== 'free' && $code !== 'gratuito') {
                LogService::safeErrorLog("[PlanContext] Codigo de plano sem acesso pago valido '{$code}' para user #{$this->user->id} - fallback para 'free'");
            }

            return $this->resolvedTier = 'free';
        }

        if ($code === 'ultra') {
            return $this->resolvedTier = 'ultra';
        }

        if ($code !== 'ultra') {
            return $this->resolvedTier = 'pro';
        }

        LogService::safeErrorLog("[PlanContext] Codigo de plano pago desconhecido '{$code}' para user #{$this->user->id} - fallback para 'pro'");

        return $this->resolvedTier = 'pro';
    }

    public function is(string $tier): bool
    {
        return $this->tier() === self::normalizeTier($tier);
    }

    public function atLeast(string $tier): bool
    {
        $tiers = self::tiers();
        $current = (int) ($tiers[$this->tier()]['rank'] ?? 0);
        $expected = (int) ($tiers[self::normalizeTier($tier)]['rank'] ?? 0);

        return $current >= $expected;
    }

    public function isFree(): bool
    {
        return $this->is('free');
    }

    public function isPro(): bool
    {
        return $this->atLeast('pro');
    }

    public function isUltra(): bool
    {
        return $this->is('ultra');
    }

    public function allows(string $feature): bool
    {
        return (bool) (self::config()['features'][$this->tier()][$feature] ?? false);
    }

    public function limit(string $key): ?int
    {
        $value = self::config()['limits'][$this->tier()][$key] ?? null;

        return $value !== null ? (int) $value : null;
    }

    public function label(): string
    {
        return (string) (self::tiers()[$this->tier()]['label'] ?? strtoupper($this->tier()));
    }

    public function upgradeTarget(): ?string
    {
        return self::tiers()[$this->tier()]['upgrade_target'] ?? null;
    }

    /**
     * @return array<string, bool|string|null>
     */
    public function summary(string $tierKey = 'plan'): array
    {
        return self::summaryForTier($this->tier(), $tierKey);
    }

    private static function normalizeTier(string $tier): string
    {
        $normalized = strtolower(trim($tier));

        return array_key_exists($normalized, self::tiers())
            ? $normalized
            : 'free';
    }
}
