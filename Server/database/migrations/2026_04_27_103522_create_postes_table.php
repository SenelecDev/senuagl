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
        Schema::create('postes', function (Blueprint $table) {
            $table->string('id_post', 20)->primary();
            $table->string('intitule', 100);
            $table->integer('effectif_theorique')->default(1);
            $table->string('tube_min', 5);
            $table->string('tube_max', 5);
            $table->string('id_unite', 20);
            $table->foreign('tube_min')->references('id_gf')->on('gfs');
            $table->foreign('tube_max')->references('id_gf')->on('gfs');
            $table->foreign('id_unite')->references('id_unite')->on('unites');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postes');
    }
};
