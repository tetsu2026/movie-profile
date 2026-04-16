<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_chatbot';

    /**
     * マイグレーション実行
     */
    public function up(): void
    {
        DB::connection('pgsql_chatbot')->statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::connection('pgsql_chatbot')->create('faq_chunks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source_path', 500);
            $table->integer('chunk_index');
            $table->text('content');
            $table->integer('tokens');
            $table->timestamp('updated_at')->useCurrent();
            $table->index('source_path');
        });

        DB::connection('pgsql_chatbot')->statement(
            'ALTER TABLE faq_chunks ADD COLUMN embedding vector(1024) NOT NULL'
        );
        DB::connection('pgsql_chatbot')->statement(
            'CREATE INDEX idx_faq_chunks_embedding ON faq_chunks USING hnsw (embedding vector_cosine_ops)'
        );
    }

    /**
     * マイグレーション取り消し
     */
    public function down(): void
    {
        Schema::connection('pgsql_chatbot')->dropIfExists('faq_chunks');
    }
};
