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
        Schema::create('financial_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');

            $table->string('type', 30)->comment('Тип проводки: deposit (оплата), refund (возврат)');

            $table->integer('amount_cents')->comment('Сумма в копейках. Защита от багов с float');

            $table->string('ledger_idempotency_key')->unique()->comment('Уникальный ID транзакции от платежки');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'type'], 'idx_ledger_order_type');
            $table->index('created_at', 'idx_ledger_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_ledger');
    }
};
