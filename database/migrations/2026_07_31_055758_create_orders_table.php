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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id', 255)->primary()->comment('Универсальный строковый ID заказа');
            $table->string('sku', 50)->index();
            $table->string('status', 20)->default('created')->comment('created, paid, delivering, delivered, failed');
            $table->integer('price_cents')->comment('Цена в копейках для исключения ошибок float');
            $table->string('supplier_id', 50)->nullable()->comment('Поставщик из этапа 3');
            $table->text('issued_product_code')->nullable()->comment('Выданный геймеру товар');
            $table->string('payment_idempotency_key')->nullable()->unique()->comment('Этап 2: защита от гонок вебхуков');
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_orders_reconciliation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
