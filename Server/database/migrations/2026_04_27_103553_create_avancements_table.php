<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('avancements', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('matricule_agent', 10);
            $table->string('id_gf_ancien', 5)->nullable();
            $table->string('id_gf_nouveau', 5)->nullable();
            $table->string('id_nr_ancien', 5)->nullable();
            $table->string('id_nr_nouveau', 5)->nullable();
            $table->foreign('matricule_agent')->references('matricule')->on('agents');
            $table->foreign('id_gf_ancien')->references('id_gf')->on('gfs');
            $table->foreign('id_gf_nouveau')->references('id_gf')->on('gfs');
            $table->foreign('id_nr_ancien')->references('id_nr')->on('nrs');
            $table->foreign('id_nr_nouveau')->references('id_nr')->on('nrs');
            $table->timestamps();
        });

        // Ajouter la contrainte CHECK après la création de la table
        DB::statement('ALTER TABLE avancements ADD CONSTRAINT check_gf_or_nr_not_null CHECK (id_gf_nouveau IS NOT NULL OR id_nr_nouveau IS NOT NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avancements');
    }
};
