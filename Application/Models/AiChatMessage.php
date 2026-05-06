<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensagem individual dentro de uma conversa de IA.
 *
 * @property int    $id
 * @property int    $conversation_id
 * @property string $role           'user' ou 'assistant'
 * @property string $content
 * @property int|null $tokens_used
 * @property string|null $intent
 * @property \Carbon\Carbon $created_at
 */
class AiChatMessage extends Model
{
    public $timestamps = false;

    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_used',
        'intent',
    ];

    protected $casts = [
        'conversation_id' => 'int',
        'tokens_used'     => 'int',
        'created_at'      => 'datetime',
    ];

    /**
     * @return BelongsTo<AiConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
