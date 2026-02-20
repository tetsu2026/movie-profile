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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();

            // ユーザーID（外部キー）
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 動画ファイル情報
            $table->string('original_filename', 255)->comment('元のファイル名');
            $table->text('original_path')->comment('S3の元動画パス');
            $table->text('encoded_path')->nullable()->comment('S3のエンコード済み動画パス');
            $table->text('thumbnail_path')->nullable()->comment('サムネイル画像パス');

            // メタデータ
            $table->integer('duration')->nullable()->comment('動画の長さ（秒）');
            $table->bigInteger('file_size')->nullable()->comment('ファイルサイズ（バイト）');

            // ステータス管理
            $table->enum('status', ['uploading', 'encoding', 'completed', 'failed'])
                ->default('uploading')
                ->comment('動画処理ステータス');

            // タイムスタンプ
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            // インデックス
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
