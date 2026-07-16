<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two related changes to customer_invites:
 *
 * 1) Add `is_snapshot` boolean. Existing rows default to 1, which
 *    preserves the current "monday.com lookup, locked fields"
 *    behavior. New open-invite rows (where the customer types
 *    their own account/branch/region/address and we create the
 *    monday row on submit) are stored with `is_snapshot = 0`.
 *
 * 2) Make account_name, branch, region, address nullable. They're
 *    still required for snapshot=1 tokens (validated at
 *    invite-creation time), but open invites don't carry them.
 *    The form validates that they're filled in at submit time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_invites', function (Blueprint $table) {
            $table->boolean('is_snapshot')
                ->default(true)
                ->after('monday_customer_id');

            $table->string('account_name', 255)->nullable()->change();
            $table->string('branch', 255)->nullable()->change();
            $table->string('region', 100)->nullable()->change();
            $table->string('address', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_invites', function (Blueprint $table) {
            $table->dropColumn('is_snapshot');
            // SQLite doesn't support re-adding NOT NULL cleanly;
            // we don't actually need a full rollback path here, but
            // the schema change is reversible on MySQL with the
            // doctrine/dbal driver.
        });
    }
};
