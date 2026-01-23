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
        Schema::table('profiles', function (Blueprint $table) {
            // ポップアップ動画ID（外部キー）
            $table->foreignId('popup_video_id')
                ->nullable()
                ->after('thumbnail_video_id')
                ->constrained('videos')
                ->onDelete('set null')
                ->comment('ポップアップに使用する動画ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['popup_video_id']);
            $table->dropColumn('popup_video_id');
        });
    }
};
