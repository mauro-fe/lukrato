<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Application\Services\LogService;
use Application\Services\FeatureGate;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'username',
        'data_nascimento',
        'id_sexo',
        'theme_preference',
        'pagarme_cliente_id',
        'gateway',
    ];

    protected $hidden = ['senha', 'password'];
    protected $casts = ['data_nascimento' => 'date:Y-m-d'];
    protected $appends = ['primeiro_nome', 'plan_renews_at', 'is_pro', 'is_gratuito'];

    /* ============================================================
     * RELACIONAMENTOS
     * ========================================================== */
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

    // 🔹 Planos
    public function assinaturas()
    {
        return $this->hasMany(AssinaturaUsuario::class, 'user_id');
    }
    public function assinaturaAtiva()
    {
        return $this->hasOne(AssinaturaUsuario::class, 'user_id')
            ->where('status', AssinaturaUsuario::ST_ACTIVE)
            ->latest('id');
    }


    public function planoAtual()
    {
        return $this->assinaturaAtiva()->with('plano')->first()?->plano;
    }

    /* ============================================================
     * BOOT / LOGIN
     * ========================================================== */
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

    /* ============================================================
     * PLANO / FEATURE GATE
     * ========================================================== */
    public function isPro(): bool
    {
        return $this->planoAtual()?->code === 'pro';
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

    /**
     * Um usuário pode ter VÁRIOS endereços.
     */
    public function enderecos()
    {
        return $this->hasMany(Endereco::class, 'user_id');
    }

    /**
     * Atalho para pegar o endereço principal (MUITO útil).
     * Um usuário pode ter UM endereço principal.
     */
    public function enderecoPrincipal()
    {
        return $this->hasOne(Endereco::class, 'user_id')
                    ->where('tipo', 'principal')
                    ->withDefault(); // withDefault() evita erros se o usuário não tiver endereço
    }
}


