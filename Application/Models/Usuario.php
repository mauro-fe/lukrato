<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\FeatureGate;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $nome
 * @property string|null $email
 * @property string|null $senha
 * @property \Carbon\Carbon|string|null $data_nascimento
 * @property int|null $id_sexo
 * @property string|null $theme_preference
 * @property string|null $external_customer_id
 * @property string|null $gateway
 * @property string|null $google_id
 *
 * @property-read mixed $plano
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|static find(int|string $id)
 * @method static \Illuminate\Database\Eloquent\Model|static create(array $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nome',
        'email',
        'avatar',
        'avatar_focus_x',
        'avatar_focus_y',
        'avatar_zoom',
        'senha',
        'data_nascimento',
        'id_sexo',
        'theme_preference',
        'onboarding_completed_at',
        'onboarding_mode',
        'onboarding_tour_skipped_at',
        'external_customer_id',
        'gateway',
        'google_id',
        'is_admin',
        'email_verified_at',
        'email_verification_token',
        'email_verification_sent_at',
        'email_verification_reminder_sent_at',
        'original_email_hash',
        'registration_ip',
        'last_login_ip',
        'support_code',
        'telegram_chat_id',
        'telegram_verified',
    ];

    protected $hidden = ['senha', 'password', 'email_verification_token'];
    protected $casts = [
        'data_nascimento' => 'date:Y-m-d',
        'is_admin' => 'integer',
        'onboarding_completed_at' => 'datetime',
        'onboarding_tour_skipped_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'email_verification_sent_at' => 'datetime',
        'email_verification_reminder_sent_at' => 'datetime',
        'telegram_verified' => 'boolean',
    ];
    protected $appends = ['primeiro_nome', 'plan_renews_at', 'is_pro', 'is_gratuito', 'is_ultra', 'plan_tier', 'onboarding_completed'];

    use SoftDeletes;

    /** @var AssinaturaUsuario|null|false  false = not loaded yet */
    private $cachedAssinatura = false;

    public function setSenhaAttribute($value): void
    {
        $raw = (string) $value;
        if ($raw === '') {
            $this->attributes['senha'] = $raw;
            return;
        }

        $this->attributes['senha'] = self::valueLooksHashed($raw)
            ? $raw
            : password_hash($raw, PASSWORD_BCRYPT);
    }


    public function categorias()
    {
        return $this->hasMany(Categoria::class, 'user_id');
    }
    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class, 'user_id');
    }
    public function contas()
    {
        return $this->hasMany(Conta::class, 'user_id');
    }

    public function getIsProAttribute(): bool
    {
        return $this->isPro();
    }
    public function getIsGratuitoAttribute(): bool
    {
        return $this->isGratuito();
    }
    public function getIsUltraAttribute(): bool
    {
        return $this->isUltra();
    }
    public function getPlanTierAttribute(): string
    {
        return $this->planTier();
    }

    public function getOnboardingCompletedAttribute(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Marca o onboarding como completo com o modo escolhido
     * @param string $mode 'guided' ou 'self'
     */
    public function markOnboardingComplete(string $mode = 'guided'): bool
    {
        $this->onboarding_completed_at = now();
        $this->onboarding_mode = $mode;
        return $this->save();
    }

    /**
     * Marca que o tour foi pulado (usuário clicou em "Pular Tutorial")
     */
    public function skipOnboardingTour(): bool
    {
        $this->onboarding_tour_skipped_at = now();
        return $this->save();
    }

    /**
     * Verifica se o tour guiado deve ser exibido
     * Retorna true apenas se:
     * - Onboarding foi completado (escolheu uma opção)
     * - Modo é 'guided'
     * - Tour não foi pulado
     */
    public function shouldShowGuidedTour(): bool
    {
        return $this->onboarding_completed_at !== null
            && $this->onboarding_mode === 'guided'
            && $this->onboarding_tour_skipped_at === null;
    }

    /**
     * Retorna o status completo do onboarding
     */
    public function getOnboardingStatus(): array
    {
        return [
            'completed' => $this->onboarding_completed_at !== null,
            'completed_at' => $this->onboarding_completed_at,
            'mode' => $this->onboarding_mode,
            'tour_skipped' => $this->onboarding_tour_skipped_at !== null,
            'tour_skipped_at' => $this->onboarding_tour_skipped_at,
            'should_show_tour' => $this->shouldShowGuidedTour(),
        ];
    }

    public function assinaturas()
    {
        return $this->hasMany(AssinaturaUsuario::class, 'user_id');
    }
    public function assinaturaAtiva()
    {
        return $this->hasOne(AssinaturaUsuario::class, 'user_id')
            ->where(function ($query) {
                $query->where('status', AssinaturaUsuario::ST_ACTIVE)
                    // Incluir assinaturas canceladas que ainda estão no período pago
                    ->orWhere(function ($q) {
                        $q->where('status', AssinaturaUsuario::ST_CANCELED)
                            ->where('renova_em', '>', now());
                    });
            })
            ->latest('id');
    }


    protected function resolvedAssinatura(): ?AssinaturaUsuario
    {
        if ($this->cachedAssinatura === false) {
            $this->cachedAssinatura = $this->assinaturaAtiva()->with('plano')->first();
        }
        return $this->cachedAssinatura;
    }

    public function clearAssinaturaCache(): void
    {
        $this->cachedAssinatura = false;
    }

    public function planoAtual()
    {
        return $this->resolvedAssinatura()?->plano;
    }

    /**
     * Verifica se o email do usuário foi verificado
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Marca o email como verificado
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ])->save();
    }

    /**
     * Gera um novo token de verificação de email
     */
    public function generateEmailVerificationToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->forceFill([
            'email_verification_token' => $token,
            'email_verification_sent_at' => now(),
        ])->save();

        return $token;
    }

    /**
     * Busca usuário pelo token de verificação
     */
    public static function findByVerificationToken(string $token): ?self
    {
        if (empty($token)) {
            return null;
        }

        return self::where('email_verification_token', $token)->first();
    }


    /**
     * Gera um código de suporte único no formato LUK-XXXX-XXXX
     * Usa caracteres sem ambiguidade (sem 0/O, 1/I/L)
     */
    public static function generateSupportCode(): string
    {
        // Caracteres sem ambiguidade visual
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $part1 = '';
            $part2 = '';
            for ($i = 0; $i < 4; $i++) {
                $part1 .= $chars[random_int(0, strlen($chars) - 1)];
                $part2 .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $code = "LUK-{$part1}-{$part2}";

            // Verifica unicidade
            if (!self::where('support_code', $code)->exists()) {
                return $code;
            }
        }

        // Fallback com timestamp
        $ts = strtoupper(base_convert((string)time(), 10, 36));
        return 'LUK-' . substr($ts, 0, 4) . '-' . substr($ts, 4, 4);
    }

    /**
     * Busca usuário pelo código de suporte
     */
    public static function findBySupportCode(string $code): ?self
    {
        return self::where('support_code', strtoupper(trim($code)))->first();
    }

    protected static function boot()
    {
        parent::boot();

        // Gera support_code automaticamente ao criar
        static::creating(function (Usuario $u) {
            if (empty($u->support_code)) {
                $u->support_code = self::generateSupportCode();
            }
        });

        static::saving(function (Usuario $u) {
            if (!empty($u->email)) $u->email = trim(strtolower($u->email));
            if (isset($u->nome))   $u->nome  = trim((string)$u->nome);

            if (!empty($u->data_nascimento)) {
                $ts = strtotime((string)$u->data_nascimento);
                $u->data_nascimento = $ts ? date('Y-m-d', $ts) : null;
            }
        });
    }

    public static function authenticate(string $email, string $password): ?self
    {
        $user = self::whereRaw('LOWER(email)=?', [trim(strtolower($email))])->first();

        if ($user && password_verify($password, $user->senha)) {
            return $user;
        }

        LogService::warning('Tentativa de login inválida', ['email' => $email]);
        return null;
    }

    private static function valueLooksHashed(string $v): bool
    {
        $p = substr($v, 0, 4);
        if (in_array($p, ['$2y$', '$2a$'])) return true;
        if (str_starts_with($v, '$argon2i') || str_starts_with($v, '$argon2id')) return true;
        $i = password_get_info($v);
        return !empty($i['algo']);
    }


    public function isPro(): bool
    {
        $assinatura = $this->resolvedAssinatura();

        if (!$assinatura) {
            return false;
        }

        // Verifica se o plano é "pro" (qualquer código que NÃO seja free/gratuito)
        $code = strtolower((string) ($assinatura->plano?->code ?? ''));
        if (in_array($code, ['free', 'gratuito', ''], true)) {
            return false;
        }

        // Verifica data de renovação
        if (!$assinatura->renova_em) {
            return false;
        }

        $renewsAt = \Carbon\Carbon::parse($assinatura->renova_em)->startOfDay();
        $now = \Carbon\Carbon::now();

        // CANCELADA: Tem acesso até o fim do período pago
        if ($assinatura->status === AssinaturaUsuario::ST_CANCELED) {
            return $renewsAt->isFuture();
        }

        // ATIVA: Tem acesso até o fim do período + 3 dias de carência
        if ($assinatura->status === AssinaturaUsuario::ST_ACTIVE) {
            // Se ainda não venceu, é PRO
            if ($renewsAt->isFuture()) {
                return true;
            }

            // Se venceu, verifica período de carência (3 dias)
            // O bloqueio acontece às 23:59:59 do último dia de carência
            // Exemplo: venceu dia 19, carência = 3 dias, bloqueia dia 22 às 23:59:59
            $gracePeriodDays = 3;
            $blockedAt = $renewsAt->copy()->addDays($gracePeriodDays)->endOfDay();

            // Ainda está no período de carência se a data atual é ANTES do momento de bloqueio
            if ($now->isBefore($blockedAt)) {
                return true;
            }

            // Passou do período de carência - não é mais PRO
            return false;
        }

        // Status expired ou outro = não é PRO
        if ($assinatura->status === AssinaturaUsuario::ST_EXPIRED) {
            return false;
        }

        return false;
    }

    public function isGratuito(): bool
    {
        return !$this->isPro();
    }

    public function isUltra(): bool
    {
        return $this->isPro() && strtolower((string) ($this->resolvedAssinatura()?->plano?->code ?? '')) === 'ultra';
    }

    /**
     * Retorna o tier do plano: 'free', 'pro' ou 'ultra'
     */
    public function planTier(): string
    {
        return FeatureGate::planTier($this);
    }

    public function getPlanRenewsAtAttribute(): ?string
    {
        return $this->resolvedAssinatura()?->renova_em?->format('Y-m-d H:i:s');
    }

    public function podeAcessar(string $feature): bool
    {
        return FeatureGate::allows($this, $feature);
    }

    public function getPrimeiroNomeAttribute(): string
    {
        $p = trim((string)$this->nome);
        return $p === '' ? '' : explode(' ', $p)[0];
    }


    public function enderecos()
    {
        return $this->hasMany(Endereco::class, 'user_id');
    }

    public function enderecoPrincipal()
    {
        return $this->hasOne(Endereco::class, 'user_id')
            ->where('tipo', 'principal')
            ->withDefault();
    }
}
