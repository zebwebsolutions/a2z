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
        Schema::create('used_device_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('condition_grade', ['A', 'B', 'C', 'D']);
            $table->unsignedTinyInteger('battery_health')->nullable();

            $table->boolean('box_available')->default(false);
            $table->boolean('charger_available')->default(false);
            $table->boolean('headphones_available')->default(false);

            $table->unsignedSmallInteger('warranty_days')->default(0);

            $table->string('imei', 20)->nullable()->unique();
            $table->boolean('imei_verified')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
