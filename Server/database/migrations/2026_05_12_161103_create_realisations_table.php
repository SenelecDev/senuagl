<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('realisations');

        Schema::create('realisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('compte_id')->constrained('comptes')->cascadeOnDelete();
            $table->decimal('montant_realise', 15, 2);
            $table->unsignedTinyInteger('mois');
            $table->unsignedSmallInteger('annee');
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'compte_id', 'annee', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisations');
    }
};
