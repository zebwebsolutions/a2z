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
        Schema::table('used_device_details', function (Blueprint $table) {
            $table->dropColumn('condition_grade');

            $table->enum('device_condition', ['used', 'refurbished'])
                ->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('used_device_details', function (Blueprint $table) {
            $table->dropColumn('device_condition');

            $table->enum('condition_grade', ['A', 'B', 'C', 'D']);
        });
    }
};
