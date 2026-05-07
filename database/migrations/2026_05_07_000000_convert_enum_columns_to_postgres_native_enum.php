<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * videos.status / users.role を PostgreSQL ネイティブ ENUM 型へ変換する。
 *
 * 背景:
 * Laravel の $table->enum() は PostgreSQL では VARCHAR + CHECK 制約として実装される。
 * 一方、Node.js 版（Prisma）は同じカラムをネイティブ ENUM 型として扱う前提で
 * クエリを発行するため（'value'::enum_type のような型キャスト）、両者の不一致で
 * INSERT/UPDATE が type "..." does not exist エラーになる。
 *
 * このマイグレーションでスキーマを Prisma の期待に揃える。本番 DB は手動 SQL で
 * 既に同等の変換を実施済みのため、新規環境向けの再現性を担保する目的で追加した。
 */
return new class extends Migration
{
    /**
     * 対象は PostgreSQL のみ。他ドライバ（SQLite テスト等）ではスキップする。
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 1. ENUM 型を作成（既存ならスキップして冪等にする）
        if (! $this->typeExists('videos_status')) {
            DB::statement("CREATE TYPE videos_status AS ENUM ('uploading', 'encoding', 'completed', 'failed')");
        }
        if (! $this->typeExists('users_role')) {
            DB::statement("CREATE TYPE users_role AS ENUM ('admin', 'user')");
        }

        // 2. videos.status: 既に native ENUM ならスキップ
        if (Schema::hasTable('videos') && $this->columnUdtName('videos', 'status') !== 'videos_status') {
            DB::statement('ALTER TABLE videos DROP CONSTRAINT IF EXISTS videos_status_check');
            DB::statement('ALTER TABLE videos ALTER COLUMN status DROP DEFAULT');
            DB::statement('ALTER TABLE videos ALTER COLUMN status TYPE videos_status USING status::videos_status');
            DB::statement("ALTER TABLE videos ALTER COLUMN status SET DEFAULT 'uploading'::videos_status");
        }

        // 3. users.role: 既に native ENUM ならスキップ
        if (Schema::hasTable('users') && $this->columnUdtName('users', 'role') !== 'users_role') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement('ALTER TABLE users ALTER COLUMN role DROP DEFAULT');
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE users_role USING role::users_role');
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'::users_role");
        }
    }

    /**
     * VARCHAR + CHECK 制約形式へ戻す。
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('videos') && $this->columnUdtName('videos', 'status') === 'videos_status') {
            DB::statement('ALTER TABLE videos ALTER COLUMN status DROP DEFAULT');
            DB::statement('ALTER TABLE videos ALTER COLUMN status TYPE varchar(255) USING status::text');
            DB::statement("ALTER TABLE videos ALTER COLUMN status SET DEFAULT 'uploading'");
            DB::statement("ALTER TABLE videos ADD CONSTRAINT videos_status_check CHECK (status IN ('uploading', 'encoding', 'completed', 'failed'))");
        }

        if (Schema::hasTable('users') && $this->columnUdtName('users', 'role') === 'users_role') {
            DB::statement('ALTER TABLE users ALTER COLUMN role DROP DEFAULT');
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE varchar(255) USING role::text');
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'user'))");
        }

        DB::statement('DROP TYPE IF EXISTS videos_status');
        DB::statement('DROP TYPE IF EXISTS users_role');
    }

    /**
     * pg_type に指定の型が存在するか確認する。
     */
    private function typeExists(string $typeName): bool
    {
        $row = DB::selectOne('SELECT 1 AS exists FROM pg_type WHERE typname = ?', [$typeName]);

        return $row !== null;
    }

    /**
     * 指定カラムの基底型名（udt_name）を返す。
     */
    private function columnUdtName(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'SELECT udt_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
            [$table, $column]
        );

        return $row->udt_name ?? null;
    }
};
