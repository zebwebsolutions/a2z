<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('tracks_inventory_by_unit')->default(false)->after('stock');
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('imei_1', 50)->nullable()->unique();
            $table->string('imei_2', 50)->nullable()->unique();
            $table->string('serial_number', 100)->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('status', 20)->default('available')->index();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tracks_inventory_by_unit');
        });
    }
};
