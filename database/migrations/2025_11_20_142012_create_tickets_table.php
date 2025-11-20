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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code_text', 30)->unique()->index();
            $table->string('holder_name', 255);
            $table->date('dob');
            $table->string('ticket_type', 10);
            $table->string('payment_code', 15)->unique()->index();
            $table->string('payment_ref', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->boolean('d4_used')->default(false);
            $table->boolean('d5_used')->default(false);
            $table->boolean('d6_used')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
