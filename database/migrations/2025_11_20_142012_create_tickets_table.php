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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('event_ticket_type_id')->nullable();
            $table->string('qr_code_text', 30)->unique()->index();
            $table->string('holder_name', 255);
            $table->string('email', 255);
            $table->date('dob');
            $table->string('payment_ref', 255)->nullable()->unique()->index();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('event_id');
            $table->index('event_ticket_type_id');
            $table->index('is_verified');
            $table->index('used_at');
            $table->index('is_vip');
            $table->index('email');
            $table->index(['user_id', 'is_verified']);
            $table->index(['event_id', 'is_verified']);
            $table->index(['event_id', 'used_at']);
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
