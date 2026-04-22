<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqChunk extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_path',
        'chunk_index',
        'content',
        'embedding',
        'tokens',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
