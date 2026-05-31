<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * 1. Samakan nama kolom branch.
         * Kalau schema lama masih pakai library_branch_id, rename ke branch_id.
         */
        if (
            Schema::hasColumn('cart_items', 'library_branch_id')
            && ! Schema::hasColumn('cart_items', 'branch_id')
        ) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->renameColumn('library_branch_id', 'branch_id');
            });
        }

        /*
         * 2. Drop kolom is_selected karena state selected cukup di-handle local mobile.
         */
        if (Schema::hasColumn('cart_items', 'is_selected')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('is_selected');
            });
        }

        /*
         * 3. Pastikan timestamps ada.
         */
        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('added_at');
            }

            if (! Schema::hasColumn('cart_items', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        /*
         * 4. Drop index lama/partial kalau migration sebelumnya sempat setengah jalan.
         */
        $this->dropIndexIfExists('cart_items', 'uq_cart_item');
        $this->dropIndexIfExists('cart_items', 'uq_cart_item_book_branch');
        $this->dropIndexIfExists('cart_items', 'uq_cart_item_branch');

        /*
         * 5. Bersihkan duplicate sebelum unique index dibuat.
         *
         * Aturan:
         * - Untuk duplicate cart_id + book_id + branch_id, keep 1 row.
         * - Untuk duplicate cart_id + branch_id, keep 1 row.
         *
         * Karena business rule baru adalah maksimal 1 buku per branch,
         * maka data lain di branch yang sama akan dihapus.
         */
        $this->removeDuplicateCartItemsByBookAndBranch();
        $this->removeDuplicateCartItemsByBranch();

        /*
         * 6. Baru tambahkan unique index.
         */
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'book_id', 'branch_id'], 'uq_cart_item_book_branch');
            $table->unique(['cart_id', 'branch_id'], 'uq_cart_item_branch');
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('cart_items', 'uq_cart_item_book_branch');
        $this->dropIndexIfExists('cart_items', 'uq_cart_item_branch');

        if (! Schema::hasColumn('cart_items', 'is_selected')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->boolean('is_selected')->default(false)->after('branch_id');
            });
        }
    }

    private function removeDuplicateCartItemsByBookAndBranch(): void
    {
        DB::statement("
            DELETE ci
            FROM cart_items ci
            INNER JOIN (
                SELECT cart_id, book_id, branch_id, MIN(id) AS keep_id
                FROM cart_items
                GROUP BY cart_id, book_id, branch_id
                HAVING COUNT(*) > 1
            ) dup
                ON dup.cart_id = ci.cart_id
                AND dup.book_id = ci.book_id
                AND dup.branch_id = ci.branch_id
            WHERE ci.id <> dup.keep_id
        ");
    }

    private function removeDuplicateCartItemsByBranch(): void
    {
        DB::statement("
            DELETE ci
            FROM cart_items ci
            INNER JOIN (
                SELECT cart_id, branch_id, MIN(id) AS keep_id
                FROM cart_items
                GROUP BY cart_id, branch_id
                HAVING COUNT(*) > 1
            ) dup
                ON dup.cart_id = ci.cart_id
                AND dup.branch_id = ci.branch_id
            WHERE ci.id <> dup.keep_id
        ");
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