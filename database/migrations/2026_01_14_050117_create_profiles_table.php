<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            // ユーザーID（外部キー、一意制約）
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // プロフィール情報
            $table->string('name', 50)->comment('表示名');
            $table->text('biography')->nullable()->comment('自己紹介文');

            // サムネイル動画（外部キー）
            $table->foreignId('thumbnail_video_id')
                ->nullable()
                ->constrained('videos')
                ->onDelete('set null')
                ->comment('サムネイルに使用する動画ID');

            // 動画表示順序（JSON配列で動画IDを保存）
            $table->json('video_order')->nullable()->comment('動画の表示順序');

            // 公開設定
            $table->boolean('is_public')->default(true)->comment('プロフィールの公開状態');

            // タイムスタンプ
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            // インデックス
            $table->index('is_public');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
