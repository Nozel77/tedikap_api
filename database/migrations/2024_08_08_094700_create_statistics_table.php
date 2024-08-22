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
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('date');
            $table->integer('total_sales')->nullable();
            $table->integer('average_per_week')->nullable();
            $table->integer('earning_growth')->nullable();
            $table->integer('total_pcs_sold')->nullable();
            $table->integer('total_income')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
