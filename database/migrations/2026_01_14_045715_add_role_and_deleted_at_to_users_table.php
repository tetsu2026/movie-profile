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
        Schema::table('users', function (Blueprint $table) {
            // ユーザー権限カラムを追加
            $table->enum('role', ['admin', 'user'])->default('user')->after('email');

            // ソフトデリート用カラムを追加
            $table->timestamp('deleted_at')->nullable()->after('updated_at');

            // deleted_at にインデックスを追加（検索パフォーマンス向上）
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // インデックスを削除
            $table->dropIndex(['deleted_at']);

            // カラムを削除
            $table->dropColumn(['role', 'deleted_at']);
        });
    }
};
