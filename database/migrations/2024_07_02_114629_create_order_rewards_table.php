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
        Schema::create('order_rewards', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('cart_reward_id');
            $table->foreign('cart_reward_id')->references('id')->on('cart_rewards')->onDelete('cascade');
            $table->integer('total_point')->default(0);
            $table->string('status')->default('menunggu konfirmasi');
            $table->string('status_description');
            $table->string('whatsapp');
            $table->string('schedule_pickup')->nullable();
            $table->string('icon_status');
            $table->string('order_type');
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_rewards');
    }
};
