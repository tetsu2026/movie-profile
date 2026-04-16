<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role',
        'content',
        'context_chunks',
    ];

    protected $casts = [
        'context_chunks' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * メッセージの所有ユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
