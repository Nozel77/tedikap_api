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
        Schema::create('order_reward_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_reward_id');
            $table->foreign('order_reward_id')->references('id')->on('order_rewards')->onDelete('cascade');
            $table->unsignedBigInteger('reward_product_id');
            $table->foreign('reward_product_id')->references('id')->on('reward_products')->onDelete('cascade');
            $table->enum('item_type', ['product', 'reward'])->default('product');
            $table->enum('temperatur', ['hot', 'ice'])->nullable();
            $table->enum('size', ['regular', 'large'])->nullable();
            $table->enum('ice', ['less', 'normal'])->nullable();
            $table->enum('sugar', ['less', 'normal'])->nullable();
            $table->text('note')->nullable();
            $table->integer('quantity');
            $table->integer('points')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_reward_items');
    }
};
