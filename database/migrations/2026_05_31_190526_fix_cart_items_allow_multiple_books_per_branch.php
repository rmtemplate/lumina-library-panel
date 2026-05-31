<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasColumn('cart_items', 'library_branch_id')
            && ! Schema::hasColumn('cart_items', 'branch_id')
        ) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->renameColumn('library_branch_id', 'branch_id');
            });
        }

        $this->dropIndexIfExists('cart_items', 'uq_cart_item_branch');
        $this->dropIndexIfExists('cart_items', 'uq_cart_item');
        $this->dropIndexIfExists('cart_items', 'uq_cart_item_book_branch');

        $this->removeDuplicateCartItemsByBookAndBranch();

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'book_id', 'branch_id'], 'uq_cart_item_book_branch');
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('cart_items', 'uq_cart_item_book_branch');
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