<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('used_device_details', function (Blueprint $table) {
            if (!Schema::hasColumn('used_device_details', 'cable_available')) {
                $table->boolean('cable_available')->default(false)->after('box_available');
            }
        });

        $driver = DB::getDriverName();

        // 1) Temporarily allow both legacy and new values
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE used_device_details MODIFY device_condition ENUM('used','refurbished','A+','A','B','C') NOT NULL");
        }

        // 2) Normalize legacy values into new grading
        DB::table('used_device_details')
            ->where('device_condition', 'used')
            ->update(['device_condition' => 'B']);

        DB::table('used_device_details')
            ->where('device_condition', 'refurbished')
            ->update(['device_condition' => 'A']);

        // 3) Tighten enum to new values only
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE used_device_details MODIFY device_condition ENUM('A+','A','B','C') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        // Temporarily allow both sets for safe mapping back
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE used_device_details MODIFY device_condition ENUM('used','refurbished','A+','A','B','C') NOT NULL");
        }

        DB::table('used_device_details')
            ->whereIn('device_condition', ['A+', 'A'])
            ->update(['device_condition' => 'refurbished']);

        DB::table('used_device_details')
            ->whereIn('device_condition', ['B', 'C'])
            ->update(['device_condition' => 'used']);

        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE used_device_details MODIFY device_condition ENUM('used','refurbished') NOT NULL");
        }

        Schema::table('used_device_details', function (Blueprint $table) {
            if (Schema::hasColumn('used_device_details', 'cable_available')) {
                $table->dropColumn('cable_available');
            }
        });
    }
};
