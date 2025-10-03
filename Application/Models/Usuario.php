<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Application\Services\LogService;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'theme_preference',
        'username',
        'data_nascimento',
        'id_sexo',
        'plano',
        'anuncios_desativados',
        'gateway',
        'pagarme_cliente_id',
        'pagarme_assinatura_id',
        'plano_renova_em',
    ];

    protected $hidden = ['senha', 'password'];

    protected $casts = [
        'data_nascimento' => 'date:Y-m-d',
        'anuncios_desativados' => 'boolean',
        'plano_renova_em' => 'datetime',
    ];

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

    // ----------------- Novos/ajustados -----------------
    public function sexo()
    {
        return $this->belongsTo(Sexo::class, 'id_sexo', 'id_sexo');
    }
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'id_usuario');
    }
    public function telefones()
    {
        return $this->hasMany(Telefone::class, 'id_usuario');
    }

    public function cpfDocumento()
    {
        return $this->hasOne(Documento::class, 'id_usuario')
            ->whereHas('tipo', fn($q) => $q->where('ds_tipo', 'CPF'));
    }

    // Telefone "principal": o primeiro cadastrado
    public function telefonePrincipal()
    {
        return $this->hasOne(Telefone::class, 'id_usuario', 'id')
            ->orderBy('id_telefone');
    }

    // -------- Helpers de leitura (opcional, úteis no controller/API) --------
    public function getCpfNumeroAttribute(): ?string
    {
        return $this->cpfDocumento()->value('numero');
    }

    public function getTelefoneFormatadoAttribute(): ?string
    {
        $tel = $this->telefonePrincipal()->first();
        if (!$tel) return null;
        $ddd = $tel->ddd()->value('codigo');
        return $ddd ? sprintf('(%s) %s', $ddd, $tel->numero) : $tel->numero;
    }

    // ----------------- Boot: limpo (sem normalizar cpf/telefone/sexo) -----------------
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Usuario $u) {
            if (!empty($u->email))  $u->email = trim(strtolower($u->email));
            if (isset($u->nome))    $u->nome  = trim((string)$u->nome);

            if (!empty($u->data_nascimento)) {
                $ts = strtotime((string)$u->data_nascimento);
                $u->data_nascimento = $ts ? date('Y-m-d', $ts) : null;
            }

            // hash de senha garantido
            if ($u->isDirty('senha')) {
                $raw = (string) $u->senha;
                if ($raw !== '' && !self::valueLooksHashed($raw)) {
                    $u->attributes['senha'] = password_hash($raw, PASSWORD_BCRYPT);
                }
            }
        });
    }

    // ----------------- Scopes úteis -----------------
    public function scopeByEmail($query, string $email)
    {
        return $query->whereRaw('LOWER(email) = ?', [trim(strtolower($email))]);
    }

    public function scopeByUsername($query, string $username)
    {
        return $query->where('username', trim($username));
    }

    // se ainda precisar buscar por CPF, agora via join com documentos
    public function scopeByCpf($query, string $cpf)
    {
        $cpf = preg_replace('/\D+/', '', $cpf);
        return $query->whereHas('cpfDocumento', fn($q) => $q->where('numero', $cpf));
    }

    // ----------------- Senha -----------------
    public function setSenhaAttribute($value): void
    {
        if ($value === null || $value === '') return;
        $val = (string) $value;
        $this->attributes['senha'] = self::valueLooksHashed($val)
            ? $val
            : password_hash($val, PASSWORD_BCRYPT);
    }
    public function setPasswordAttribute($value): void
    {
        $this->setSenhaAttribute($value);
    }
    public function getPasswordAttribute(): ?string
    {
        return $this->attributes['senha'] ?? null;
    }

    private static function valueLooksHashed(string $value): bool
    {
        $prefix = substr($value, 0, 4);
        if ($prefix === '$2y$' || $prefix === '$2a$') return true;
        if (substr($value, 0, 9) === '$argon2i' || substr($value, 0, 10) === '$argon2id') return true;
        $info = password_get_info($value);
        return !empty($info) && !empty($info['algo']) && $info['algo'] !== 0;
    }

    // ----------------- Ajudinhas -----------------
    public function getPrimeiroNomeAttribute(): string
    {
        $p = trim((string) $this->nome);
        return $p === '' ? '' : explode(' ', $p)[0];
    }

    public static function authenticate(string $email, string $password): ?self
    {
        $user = self::byEmail($email)->first();
        if ($user && password_verify($password, $user->senha)) return $user;
        LogService::warning('Tentativa de login inválida', ['email' => $email]);
        return null;
    }

    public function isSemAnuncios(): bool
    {
        return $this->anuncios_desativados === true;
    }

    // Helper: verificar se está no plano gratuito
    public function isGratuito(): bool
    {
        return $this->plano === 'gratuito';
    }
    // === ALIASES EM INGLÊS PARA COMPATIBILIDADE ===

    // ads_disabled -> anuncios_desativados
    public function getAdsDisabledAttribute(): bool
    {
        return (bool) ($this->attributes['anuncios_desativados'] ?? 0);
    }

    public function setAdsDisabledAttribute($value): void
    {
        $this->attributes['anuncios_desativados'] = (int) ((bool) $value);
    }

    // plan_renews_at -> plano_renova_em
    public function getPlanRenewsAtAttribute(): ?string
    {
        // retorna ISO simples para a view/js (ou ajuste para o formato que preferir)
        if (empty($this->attributes['plano_renova_em'])) return null;
        $ts = strtotime((string) $this->attributes['plano_renova_em']);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    public function setPlanRenewsAtAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['plano_renova_em'] = null;
            return;
        }
        $ts = is_numeric($value) ? (int)$value : strtotime((string)$value);
        $this->attributes['plano_renova_em'] = $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
