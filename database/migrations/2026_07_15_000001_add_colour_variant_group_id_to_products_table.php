<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'colour_variant_group_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->uuid('colour_variant_group_id')->nullable()->after('brand_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'colour_variant_group_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('colour_variant_group_id');
            });
        }
    }
};
