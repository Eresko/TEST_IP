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
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->dateTime('expires_at');
            $table->foreignId('slot_id')->constrained('slots')->onDelete('cascade');
            $table->uuid('idempotency_key')->unique();
            $table->enum('status', ['held', 'confirmed', 'cancelled'])->default('held');


            $table->json('response_data')->nullable();
            $table->timestamps();

            
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holds');
    }
};
