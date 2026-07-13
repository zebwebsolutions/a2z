<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'customer_address')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('customer_address')->nullable()->after('customer_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'customer_address')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('customer_address');
            });
        }
    }
};
