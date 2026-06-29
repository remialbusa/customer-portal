<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add `address` to the `users` table to mirror the
     * Customer Details board's `location_mm4e2wr3` column.
     *
     * Customers register with an address (street + city), so the
     * their assigned TSP has a physical location to roll a truck
     * to. TSPs and managers don't need it; we leave the column
     * nullable and just store it for customers.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('branch');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
