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
            // テーマカラー（HEX形式 例: #667eea）
            $table->string('theme_color', 7)
                ->default('#667eea')
                ->after('is_public')
                ->comment('公開ページのテーマカラー（HEX）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('theme_color');
        });
    }
};
