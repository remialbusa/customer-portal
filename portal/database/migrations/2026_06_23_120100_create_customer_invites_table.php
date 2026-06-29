<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-use registration tokens for customer self-registration.
     *
     * Flow:
     *   1. Service coordinator adds a customer row in the monday.com
     *      Customer Details board (name, account, branch, region,
     *      address, email).
     *   2. Admin runs `php artisan portal:invite-customer {email}`.
     *      This reads the row from monday, snapshots the immutable
     *      fields (account_name, branch, region, address) into the
     *      token, and returns a unique URL like
     *      https://portal.test/register/{token}.
     *   3. Admin sends the link to the customer (email / WhatsApp).
     *   4. Customer visits the link, the form is pre-filled with
     *      the immutable fields, and the customer only enters
     *      their name + password.
     *   5. The server validates: token exists, not used, not
     *      expired, and the email in the token still exists in
     *      the monday.com Customer Details board. Then it
     *      creates the user, marks the token used, and logs the
     *      customer in.
     *
     * This guarantees the portal has zero random public sign-ups.
     * Every account is tied to a real monday.com record.
     */
    public function up(): void
    {
        Schema::create('customer_invites', function (Blueprint $table) {
            $table->id();

            // Cryptographically random URL token (URL-safe base64,
            // 32 bytes = 43 chars). Indexed for the GET lookup.
            $table->string('token', 64)->unique();

            // Customer identity as captured from monday.com at
            // invite-generation time. The customer does NOT type
            // these — they come from the snapshot.
            $table->string('email', 191);
            $table->string('account_name', 255);
            $table->string('branch', 255);
            $table->string('region', 100)->nullable();
            $table->string('address', 500)->nullable();

            // The monday.com Customer Details item id. Stored so
            // we can verify the record still exists and, later,
            // link tickets back to the customer.
            $table->string('monday_customer_id', 32)->nullable();

            // Token lifecycle.
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('invited_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Common lookups.
            $table->index('email');
            $table->index('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invites');
    }
};
