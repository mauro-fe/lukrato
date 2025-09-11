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
            // normalização básica
            if (!empty($u->email))  $u->email = trim(strtolower($u->email));
            if (isset($u->nome))    $u->nome  = trim((string)$u->nome);

            // cpf/telefone: somente dígitos ou null
            if (array_key_exists('cpf', $u->attributes)) {
                $dig = preg_replace('/\D+/', '', (string)$u->cpf);
                $u->cpf = ($dig !== '') ? $dig : null;
            }
            if (array_key_exists('telefone', $u->attributes)) {
                $dig = preg_replace('/\D+/', '', (string)$u->telefone);
                $u->telefone = ($dig !== '') ? $dig : null;
            }

            // sexo em maiúsculo e válido
            if (array_key_exists('sexo', $u->attributes) && $u->sexo !== null) {
                $sx = strtoupper((string)$u->sexo);
                $u->sexo = in_array($sx, ['M', 'F', 'O', 'N'], true) ? $sx : null;
            }

            // data_nascimento para Y-m-d
            if (!empty($u->data_nascimento)) {
                $ts = strtotime((string)$u->data_nascimento);
                $u->data_nascimento = $ts ? date('Y-m-d', $ts) : null;
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
        // não altera se null ou vazio
        if ($value === null || $value === '') return;

        // se já parecer hash, mantém
        if ($this->looksHashed((string)$value)) {
            $this->attributes['senha'] = (string)$value;
            return;
        }

        // hash (bcrypt); pode trocar para PASSWORD_DEFAULT se preferir
        $this->attributes['senha'] = password_hash((string)$value, PASSWORD_BCRYPT);
    }

    // Alias para compatibilizar $user->password no controller
    public function setPasswordAttribute($value): void
    {
        $this->setSenhaAttribute($value); // delega para o mutator acima
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->attributes['senha'] ?? null;
    }

    private function looksHashed(string $value): bool
    {
        return (
            str_starts_with($value, '$2y$') ||
            str_starts_with($value, '$2a$') ||
            str_starts_with($value, '$argon2i$') ||
            str_starts_with($value, '$argon2id$') ||
            (is_string($value) && password_get_info($value)['algo'] !== 0)
        );
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
