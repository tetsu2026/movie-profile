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
            $table->integer('retry_count')->default(0)->after('status')->comment('エンコードリトライ回数');
            $table->text('error_message')->nullable()->after('retry_count')->comment('エラーメッセージ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'error_message']);
        });
    }
};
