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
            $table->string('customer_phone_normalized', 30)->nullable()->index();
        });
        DB::table('purchases')->select('id', 'customer_phone')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $digits = preg_replace('/\D/', '', $row->customer_phone);
                if (str_starts_with($digits, '00')) {
                    $digits = substr($digits, 2);
                }
                if (strlen($digits) === 8) {
                    $digits = '965'.$digits;
                }
                DB::table('purchases')->where('id', $row->id)->update(['customer_phone_normalized' => $digits]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['customer_phone_normalized']);
            $table->dropColumn('customer_phone_normalized');
        });
    }
};
