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
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('ip'); // ip
            $table->string('port'); // port
            $table->string('type')->nullable(true); // тип прокси
            $table->string('city')->nullable(true); // город
            $table->string('status')->default('fail'); // текущий статус (предусмотреть историчность)
            $table->string('speed')->nullable(true); // скорость скачивания
            $table->string('real_ip')->nullable(true); // реальный внешний ip прокси
            $table->text('comment')->nullable(true); // комментарий
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
