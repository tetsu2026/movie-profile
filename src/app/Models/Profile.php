<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * マスアサインメント可能な属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'biography',
        'thumbnail_video_id',
        'video_order',
        'is_public',
    ];

    /**
     * キャスト設定
     *
     * @var array<string, string>
     */
    protected $casts = [
        'video_order' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * プロフィールの所有者を取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * サムネイル動画を取得
     */
    public function thumbnailVideo()
    {
        return $this->belongsTo(Video::class, 'thumbnail_video_id');
    }
}
