<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Application\Services\LogService;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['nome', 'email', 'senha'];

    protected $hidden = ['senha'];

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
            if (!empty($u->email)) {
                $u->email = trim(strtolower($u->email));
            }
            if (!empty($u->nome)) {
                $u->nome = trim($u->nome);
            }
        });
    }

    // ---- SCOPES ----
    public function scopeByEmail($query, string $email)
    {
        return $query->whereRaw('LOWER(email) = ?', [trim(strtolower($email))]);
    }

    // ---- MUTATORS ----
    public function setSenhaAttribute($value): void
    {
        if (!empty($value) && !$this->isPasswordHashed($value)) {
            $this->attributes['senha'] = password_hash($value, PASSWORD_DEFAULT);
        } else {
            $this->attributes['senha'] = $value;
        }
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

        if ($user && password_verify($password, $user->senha)) {
            return $user;
        }

        LogService::warning('Tentativa de login inválida', ['email' => $email]);
        return null;
    }

    private function isPasswordHashed(string $value): bool
    {
        return is_string($value) && password_get_info($value)['algo'] !== 0;
    }
}
