<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nickname')->nullable();
            $table->string('brand', 120);
            $table->string('model', 120);
            $table->string('serial_number', 120)->nullable();
            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('monday_id', 32)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
