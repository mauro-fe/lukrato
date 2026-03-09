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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'titulo',
    ];

    protected $casts = [
        'user_id'    => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
