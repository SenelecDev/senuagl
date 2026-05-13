<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('previsions');
        Schema::dropIfExists('budget_previsions');

        Schema::create('budget_previsions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('compte_id')->constrained('comptes')->cascadeOnDelete();
            $table->decimal('montant_prevu', 15, 2);
            $table->unsignedSmallInteger('annee');
            $table->timestamps();

            $table->unique(['service_id', 'compte_id', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_previsions');
    }
};
