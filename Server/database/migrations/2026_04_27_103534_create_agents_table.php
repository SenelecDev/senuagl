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
        Schema::create('agents', function (Blueprint $table) {
            $table->string('matricule', 10)->primary();
            $table->string('nom', 50);
            $table->string('prenom', 50);
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->string('lieu_naissance', 100)->nullable();
            $table->string('situation_familiale', 30)->nullable();
            $table->integer('nombre_enfants')->default(0);
            $table->date('date_embauche');
            $table->string('nationalite', 30)->default('Senegalaise');
            $table->string('id_post', 20);
            $table->string('id_gf_actuel', 5);
            $table->string('id_nr_actuel', 5);
            $table->foreign('id_post')->references('id_post')->on('postes');
            $table->foreign('id_gf_actuel')->references('id_gf')->on('gfs');
            $table->foreign('id_nr_actuel')->references('id_nr')->on('nrs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
