<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the three signature file paths to the service report.
     * We store paths (not the bytes) because the file is written to
     * Laravel's `local` disk and a separate process (the offline
     * drainer) later uploads it to Monday's file column.
     */
    public function up(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            $table->string('tsp_signature_path', 500)->nullable()
                ->comment('storage/app/signatures/{local_id}-tsp.png — pending Monday upload');

            $table->string('customer_signature_path', 500)->nullable()
                ->comment('storage/app/signatures/{local_id}-customer.png — pending Monday upload');

            $table->string('biomed_signature_path', 500)->nullable()
                ->comment('storage/app/signatures/{local_id}-biomed.png — pending Monday upload');
        });
    }

    public function down(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            $table->dropColumn([
                'tsp_signature_path',
                'customer_signature_path',
                'biomed_signature_path',
            ]);
        });
    }
};
