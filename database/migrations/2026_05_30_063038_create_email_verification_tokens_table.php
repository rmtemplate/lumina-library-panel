<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_verification_tokens')) {
            Schema::create('email_verification_tokens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('email', 255);
                $table->string('token_hash', 255);
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->boolean('is_used')->default(false);
                $table->timestamps();

                $table->index('user_id', 'idx_email_verify_user');
                $table->index('email', 'idx_email_verify_email');
                $table->index('expires_at', 'idx_email_verify_expires');
                $table->index(['email', 'is_used'], 'idx_email_verify_email_used');
            });

            return;
        }

        Schema::table('email_verification_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('email_verification_tokens', 'email')) {
                $table->string('email', 255)->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('email_verification_tokens', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('expires_at');
            }

            if (! Schema::hasColumn('email_verification_tokens', 'is_used')) {
                $table->boolean('is_used')->default(false)->after('verified_at');
            }

            if (! Schema::hasColumn('email_verification_tokens', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('email_verification_tokens', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        /*
         * Kalau email baru ditambahkan nullable, isi dari users.email agar aman.
         */
        if (Schema::hasColumn('email_verification_tokens', 'email')) {
            DB::statement("
                UPDATE email_verification_tokens evt
                INNER JOIN users u ON u.id = evt.user_id
                SET evt.email = u.email
                WHERE evt.email IS NULL
            ");
        }

        /*
         * Setelah data diisi, jadikan email NOT NULL jika database mendukung.
         * Untuk MySQL aman pakai raw statement.
         */
        if (Schema::hasColumn('email_verification_tokens', 'email')) {
            DB::statement("
                ALTER TABLE email_verification_tokens
                MODIFY email VARCHAR(255) NOT NULL
            ");
        }

        $this->addIndexIfNotExists('email_verification_tokens', 'idx_email_verify_user', ['user_id']);
        $this->addIndexIfNotExists('email_verification_tokens', 'idx_email_verify_email', ['email']);
        $this->addIndexIfNotExists('email_verification_tokens', 'idx_email_verify_expires', ['expires_at']);
        $this->addIndexIfNotExists('email_verification_tokens', 'idx_email_verify_email_used', ['email', 'is_used']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_verification_tokens')) {
            return;
        }

        Schema::table('email_verification_tokens', function (Blueprint $table) {
            $this->dropIndexIfExists('email_verification_tokens', 'idx_email_verify_user');
            $this->dropIndexIfExists('email_verification_tokens', 'idx_email_verify_email');
            $this->dropIndexIfExists('email_verification_tokens', 'idx_email_verify_expires');
            $this->dropIndexIfExists('email_verification_tokens', 'idx_email_verify_email_used');
        });

        Schema::table('email_verification_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('email_verification_tokens', 'email')) {
                $table->dropColumn('email');
            }

            if (Schema::hasColumn('email_verification_tokens', 'verified_at')) {
                $table->dropColumn('verified_at');
            }

            if (Schema::hasColumn('email_verification_tokens', 'is_used')) {
                $table->dropColumn('is_used');
            }
        });
    }

    private function addIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne("
            SELECT COUNT(1) AS count
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = ?
              AND index_name = ?
        ", [$database, $table, $indexName]);

        return (int) ($result->count ?? 0) > 0;
    }
};