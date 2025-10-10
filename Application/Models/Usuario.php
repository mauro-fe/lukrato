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

    public function telefonePrincipal()
    {
        return $this->hasOne(Telefone::class, 'id_usuario', 'id')
            ->orderBy('id_telefone');
    }

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
        $cpf = preg_replace('/\D+/', '', $cpf);
        return $query->whereHas('cpfDocumento', fn($q) => $q->where('numero', $cpf));
    }

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
}
