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
            $table->foreignId('event_date_id')->nullable()->after('event_id')->constrained('event_dates')->onDelete('cascade');
            $table->index('event_date_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['event_date_id']);
            $table->dropIndex(['event_date_id']);
            $table->dropColumn('event_date_id');
        });
    }
};
