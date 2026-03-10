<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Conversa de IA de um usuário.
 * Uma conversa agrupa múltiplas mensagens (user + assistant).
 *
 * @property int    $id
 * @property int    $user_id
 * @property string|null $titulo
 * @property string $state          idle|collecting_entity|awaiting_selection
 * @property array|null $state_data  Dados parciais do fluxo multi-turno ativo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'titulo',
        'state',
        'state_data',
    ];

    protected $casts = [
        'user_id'    => 'int',
        'state_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'state' => 'idle',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(AiChatMessage::class, 'conversation_id')->latestOfMany();
    }
}
