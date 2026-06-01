<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->index(['statut', 'date_debut', 'date_fin', 'user_id'], 'idx_demandes_calendar_scope');
        });
    }

    public function down(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropIndex('idx_demandes_calendar_scope');
        });
    }
};
