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
        Schema::create('spare_part_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('salesman_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamp('sold_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_sales');
    }
};
