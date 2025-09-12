<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Application\Services\LogService;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Inclui os novos campos e username (opcional)
    protected $fillable = [
        'nome',
        'email',
        'senha',           // campo físico no banco
        'username',        // opcional
        'cpf',             // só dígitos
        'telefone',        // só dígitos (10/11)
        'data_nascimento', // DATE (Y-m-d)
        'sexo',            // 'M','F','O','N' ou null
    ];

    // Esconde senha nas respostas JSON
    protected $hidden = ['senha', 'password'];

    // Cast da data para padrão Y-m-d
    protected $casts = [
        'data_nascimento' => 'date:Y-m-d',
    ];

    // ---- RELAÇÕES ----
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

    // ---- BOOT ----
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Usuario $u) {
            // normalizações que você já tem...
            if (!empty($u->email))  $u->email = trim(strtolower($u->email));
            if (isset($u->nome))    $u->nome  = trim((string)$u->nome);

            if (array_key_exists('cpf', $u->attributes)) {
                $dig = preg_replace('/\D+/', '', (string)$u->cpf);
                $u->cpf = ($dig !== '') ? $dig : null;
            }
            if (array_key_exists('telefone', $u->attributes)) {
                $dig = preg_replace('/\D+/', '', (string)$u->telefone);
                $u->telefone = ($dig !== '') ? $dig : null;
            }

            if (array_key_exists('sexo', $u->attributes) && $u->sexo !== null) {
                $sx = strtoupper((string)$u->sexo);
                $u->sexo = in_array($sx, ['M', 'F', 'O', 'N'], true) ? $sx : null;
            }

            if (!empty($u->data_nascimento)) {
                $ts = strtotime((string)$u->data_nascimento);
                $u->data_nascimento = $ts ? date('Y-m-d', $ts) : null;
            }

            // 🔒 Garanta hash mesmo se o mutator não rodar
            if ($u->isDirty('senha')) {
                $raw = (string) $u->senha;
                if ($raw !== '' && !self::valueLooksHashed($raw)) {
                    $u->attributes['senha'] = password_hash($raw, PASSWORD_BCRYPT);
                }
            }
        });
    }

    // ---- SCOPES ----
    public function scopeByEmail($query, string $email)
    {
        return $query->whereRaw('LOWER(email) = ?', [trim(strtolower($email))]);
    }

    public function scopeByUsername($query, string $username)
    {
        return $query->where('username', trim($username));
    }

    public function scopeByCpf($query, string $cpf)
    {
        return $query->where('cpf', preg_replace('/\D+/', '', $cpf));
    }

    // ---- MUTATORS ----
    public function setSenhaAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $val = (string) $value;

        // Se já parecer hash, mantém
        if (self::valueLooksHashed($val)) {
            $this->attributes['senha'] = $val;
            return;
        }

        // Gera hash
        $this->attributes['senha'] = password_hash($val, PASSWORD_BCRYPT);
    }

    // Alias para compatibilizar $user->password no controller
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
        // Evita funções de PHP 8 aqui
        $prefix = substr($value, 0, 4);
        if ($prefix === '$2y$' || $prefix === '$2a$') {
            return true; // bcrypt
        }
        if (substr($value, 0, 9) === '$argon2i' || substr($value, 0, 10) === '$argon2id') {
            return true; // argon2
        }

        // password_get_info é seguro; se "algo" != 0, já é hash suportado
        $info = password_get_info($value);
        return !empty($info) && !empty($info['algo']) && $info['algo'] !== 0;
    }

    // ---- ACCESSORS ----
    public function getPrimeiroNomeAttribute(): string
    {
        $p = trim((string) $this->nome);
        return $p === '' ? '' : explode(' ', $p)[0];
    }

    // ---- AUTH SIMPLES ----
    public static function authenticate(string $email, string $password): ?self
    {
        $user = self::byEmail($email)->first();

        // verifica contra 'senha' (coluna física); alias 'password' existe se precisar
        if ($user && password_verify($password, $user->senha)) {
            return $user;
        }

        LogService::warning('Tentativa de login inválida', ['email' => $email]);
        return null;
    }
}
