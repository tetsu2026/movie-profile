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
        Schema::table('videos', function (Blueprint $table) {
            // original_pathをnullableに変更（レコード作成時はまだパスが確定していないため）
            $table->text('original_path')->nullable()->comment('S3の元動画パス')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->text('original_path')->nullable(false)->comment('S3の元動画パス')->change();
        });
    }
};
