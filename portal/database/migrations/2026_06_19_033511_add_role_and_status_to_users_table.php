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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)
                ->default('customer')
                ->after('email');

            $table->string('status', 20)
                ->default('active')
                ->after('role');

            // Monday.com person id (for TSP) or customer id (for customer)
            $table->string('monday_id')->nullable()->after('status');

            // TSP-only fields
            $table->string('team', 10)->nullable()->after('monday_id');     // FSE | ITS
            $table->string('region')->nullable()->after('team');
            $table->json('skills')->nullable()->after('region');

            // Customer-only fields (collected during registration)
            $table->string('branch')->nullable()->after('skills');
            $table->string('account_name')->nullable()->after('branch');
            $table->string('brand')->nullable()->after('account_name');
            $table->string('model')->nullable()->after('brand');
            $table->string('serial_number')->nullable()->after('model');
            $table->date('installation_date')->nullable()->after('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'status', 'monday_id', 'team', 'region', 'skills',
                'branch', 'account_name', 'brand', 'model',
                'serial_number', 'installation_date',
            ]);
        });
    }
};
