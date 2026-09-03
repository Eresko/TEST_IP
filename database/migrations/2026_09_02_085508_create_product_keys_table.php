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
        Schema::create('product_keys', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->comment('Артикул товара, к которому принадлежит ключ');
            $table->string('key_code', 255)->unique()->comment('Цифровой код/ключ товара из ТЗ');
            $table->string('order_id', 255)->nullable()->unique()
                ->comment('ID заказа с нашей площадки, под который выдан этот ключ. Защита Exactly-Once.');

            $table->timestamp('assigned_at')->nullable()->comment('Время выдачи ключа');
            $table->timestamps();

            $table->index(['sku', 'order_id'], 'idx_product_keys_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_keys');
    }
};
