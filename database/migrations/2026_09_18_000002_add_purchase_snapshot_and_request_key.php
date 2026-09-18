<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('product_name')->nullable();
            $table->string('request_key', 64)->nullable()->unique();
        });
        DB::table('purchases')->select('id', 'product_id')->orderBy('id')->chunkById(200, function ($rows) {
            $names = DB::table('products')->whereIn('id', $rows->pluck('product_id')->filter())->pluck('name', 'id');
            foreach ($rows as $row) {
                DB::table('purchases')->where('id', $row->id)->update(['product_name' => $names[$row->product_id] ?? 'Deleted product']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['request_key']);
            $table->dropColumn(['request_key', 'product_name']);
        });
    }
};
