<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_previsions', function (Blueprint $table) {
            $table->unsignedTinyInteger('mois')->default(1)->after('annee');
            $table->dropUnique(['service_id', 'compte_id', 'annee']);
            $table->unique(['service_id', 'compte_id', 'annee', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::table('budget_previsions', function (Blueprint $table) {
            $table->dropUnique(['service_id', 'compte_id', 'annee', 'mois']);
            $table->unique(['service_id', 'compte_id', 'annee']);
            $table->dropColumn('mois');
        });
    }
};
