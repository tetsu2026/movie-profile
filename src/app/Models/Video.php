<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * マスアサインメント可能な属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'original_filename',
        'original_path',
        'encoded_path',
        'thumbnail_path',
        'duration',
        'file_size',
        'status',
        'retry_count',
        'error_message',
    ];

    /**
     * キャスト設定
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * 動画の所有者を取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * エンコード済み動画のURLを取得
     *
     * @return string|null
     */
    public function getEncodedUrlAttribute(): ?string
    {
        if (!$this->encoded_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->encoded_path);
    }
}
