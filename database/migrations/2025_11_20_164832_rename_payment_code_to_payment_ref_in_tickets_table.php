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
            // Drop the old nullable payment_ref column if it exists
            if (Schema::hasColumn('tickets', 'payment_ref')) {
                $table->dropColumn('payment_ref');
            }
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            // Rename payment_code to payment_ref
            $table->renameColumn('payment_code', 'payment_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Rename payment_ref back to payment_code
            $table->renameColumn('payment_ref', 'payment_code');
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            // Add back the nullable payment_ref column
            $table->string('payment_ref', 255)->nullable()->after('payment_code');
        });
    }
};
