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
    Schema::create('orders', function ($table) {
  $table->id();
  $table->foreignId('user_id')->constrained('userds')->cascadeOnDelete();
  $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

  $table->date('date');
  $table->string('city')->nullable();
  $table->enum('status', ['delivered','pending','returned'])->default('pending');

  $table->decimal('sell', 10, 2);
  $table->decimal('cost', 10, 2);
  $table->decimal('ship', 10, 2)->default(0);

  $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
