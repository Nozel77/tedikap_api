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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('regular_price');
            $table->integer('large_price');
            $table->integer('original_regular_price')->nullable();
            $table->integer('original_large_price')->nullable();
            $table->enum('category', ['tea', 'nontea', 'yakult']);
            $table->text('image');
            $table->boolean('stock')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
