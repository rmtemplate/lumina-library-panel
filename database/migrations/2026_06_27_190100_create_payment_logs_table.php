<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pure audit trail — one row per webhook notification / status-sync /
     * charge call. `borrowing_payments` remains the single source of truth
     * for CURRENT state; this table only answers "what happened, and when"
     * for debugging. Never read from for business logic.
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_logs')) {
            return;
        }

        Schema::create('payment_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('borrowing_payment_id');
            $table->string('source', 20); // webhook | sync | charge | cancel
            $table->string('transaction_status', 30)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('borrowing_payment_id')
                ->references('id')->on('borrowing_payments')
                ->onDelete('cascade');

            $table->index(['borrowing_payment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
