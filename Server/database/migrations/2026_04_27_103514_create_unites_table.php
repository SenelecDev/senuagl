<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('unites', function (Blueprint $table) {
            $table->string('id_unite', 20)->primary();
            $table->unique('id_unite');
            $table->string('nom', 100);
            $table->enum('type', ['Direction', 'Departement', 'Service', 'Cellule', 'Unite']);
            $table->string('id_parent', 20)->nullable();
            $table->foreign('id_parent')->references('id_unite')->on('unites');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unites');
    }
};
