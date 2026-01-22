<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Application\Services\LogService;
use Application\Services\FeatureGate;
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
        'senha',
        'data_nascimento',
        'id_sexo',
        'theme_preference',
        'external_customer_id',
        'gateway',
        'google_id',
        'is_admin'
    ];

    protected $hidden = ['senha', 'password'];
    protected $casts = [
        'data_nascimento' => 'date:Y-m-d',
        'is_admin' => 'integer',
    ];
    protected $appends = ['primeiro_nome', 'plan_renews_at', 'is_pro', 'is_gratuito'];

    use SoftDeletes;


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


    public function planoAtual()
    {
        return $this->assinaturaAtiva()->with('plano')->first()?->plano;
    }


    protected static function boot()
    {
        parent::boot();

        static::saving(function (Usuario $u) {
            if (!empty($u->email)) $u->email = trim(strtolower($u->email));
            if (isset($u->nome))   $u->nome  = trim((string)$u->nome);

            if (!empty($u->data_nascimento)) {
                $ts = strtotime((string)$u->data_nascimento);
                $u->data_nascimento = $ts ? date('Y-m-d', $ts) : null;
            }

            if ($u->isDirty('senha')) {
                $raw = (string) $u->senha;
                if ($raw !== '' && !self::valueLooksHashed($raw)) {
                    $u->attributes['senha'] = password_hash($raw, PASSWORD_BCRYPT);
                }
            }
        });
    }

    public static function authenticate(string $email, string $password): ?self
    {
        $user = self::whereRaw('LOWER(email)=?', [trim(strtolower($email))])->first();
        if ($user && password_verify($password, $user->senha)) return $user;

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
        $assinatura = $this->assinaturaAtiva()->with('plano')->first();

        if (!$assinatura || $assinatura->plano?->code !== 'pro') {
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
        return in_array($this->planoAtual()?->code, ['free', 'gratuito', null], true);
    }

    public function getPlanRenewsAtAttribute(): ?string
    {
        $ass = $this->assinaturaAtiva()->first();
        return $ass?->renova_em?->format('Y-m-d H:i:s');
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

    private function isProUser(int $userId): bool
    {
        try {
            /** @var Usuario|null $user */
            $user = Usuario::query()->with(['assinaturaAtiva.plano'])->find($userId);
            if (!$user) return false;

            // Se não tem assinatura ativa, é FREE
            $assinatura = $user->assinaturaAtiva;
            if (!$assinatura) return false;

            // Se tem assinatura ativa, considera PRO (com plano válido)
            // Se você tiver um plano "free" registrado no banco, pode bloquear aqui:
            $code = strtolower((string)($assinatura->plano?->code ?? ''));
            if ($code === 'free') return false;

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
