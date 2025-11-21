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
        Schema::table('tickets', function (Blueprint $table) {
            // Drop the unique index first
            $table->dropUnique(['qr_code_text']);
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            // Increase qr_code_text from 30 to 100 characters to accommodate longer ticket type names
            $table->string('qr_code_text', 100)->change();
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            // Re-add the unique index
            $table->unique('qr_code_text', 'tickets_qr_code_text_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop the unique index first
            $table->dropUnique(['qr_code_text']);
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            // Revert back to 30 characters
            $table->string('qr_code_text', 30)->change();
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            // Re-add the unique index
            $table->unique('qr_code_text', 'tickets_qr_code_text_unique');
        });
    }
};
