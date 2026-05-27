<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_previsions', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropUnique(['service_id', 'compte_id', 'annee', 'mois']);
            $table->dropColumn('service_id');
            $table->unique(['compte_id', 'annee', 'mois']);
        });

        Schema::table('realisations', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropIndex(['service_id', 'compte_id', 'annee', 'mois']);
            $table->dropColumn('service_id');
            $table->index(['compte_id', 'annee', 'mois']);
        });

        Schema::dropIfExists('services');
    }

    public function down(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('intitule');
            $table->timestamps();
        });

        Schema::table('budget_previsions', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->dropUnique(['compte_id', 'annee', 'mois']);
            $table->unique(['service_id', 'compte_id', 'annee', 'mois']);
        });

        Schema::table('realisations', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->dropIndex(['compte_id', 'annee', 'mois']);
            $table->index(['service_id', 'compte_id', 'annee', 'mois']);
        });
    }
};
