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
        Schema::table('event_ticket_types', function (Blueprint $table) {
            $table->boolean('is_adult_only')->default(false)->after('is_vip');
            $table->index('is_adult_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_ticket_types', function (Blueprint $table) {
            $table->dropIndex(['is_adult_only']);
            $table->dropColumn('is_adult_only');
        });
    }
};
