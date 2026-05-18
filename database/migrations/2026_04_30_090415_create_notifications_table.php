<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\ChannelType;
use App\Enums\StatusMessage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->text('message')->comment('Текст сообщения');
            $table->string('channel')
                ->default(ChannelType::SMS->value)
                ->comment('Канал отправки');
            $table->enum('status', array_column(StatusMessage::cases(), 'value'))
                ->comment('Статус сообщения')->default(StatusMessage::WAITING->value);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ID пользователя');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade')->comment('ID автора сообщения');

            $table->timestamps();

            $table->index(['channel','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
