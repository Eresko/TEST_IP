<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_stocks', function (Blueprint $table) {
            $table->string('sku', 50)->primary()->comment('Уникальный артикул цифрового товара');
            $table->integer('available_count')->default(0)->comment('Текущее количество доступных ключей');
            $table->boolean('is_active')->default(true)->comment('Флаг видимости товара на витрине');
            $table->timestamps();
        });

        /**
         * исключаем из индекса остатки с нулевым остатком
         */
        DB::statement('
            CREATE INDEX idx_catalog_active_stocks 
            ON catalog_stocks (is_active, available_count) 
            WHERE is_active = true AND available_count > 0
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_stocks');
    }
};
