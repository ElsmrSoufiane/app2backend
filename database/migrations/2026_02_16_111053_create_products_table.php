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
Schema::create('products', function ($table) {
  $table->id();
  $table->foreignId('user_id')->constrained('userds')->cascadeOnDelete();
  $table->string('name');
  $table->string('image_url')->nullable();

  $table->decimal('default_cost', 10, 2)->nullable();
  $table->decimal('default_ship', 10, 2)->nullable();
  $table->decimal('default_sell', 10, 2)->nullable();

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
