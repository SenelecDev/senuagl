<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projet_investissements', function (Blueprint $table) {
            $table->id();
            $table->string('code_projet');
            $table->string('libelle');
            $table->string('bailleur')->default('FONDS PROPRES');
            $table->string('cr')->nullable();
            
            $table->decimal('montant_marche', 15, 2)->default(0);
            $table->decimal('cout_projet', 15, 2)->default(0);
            
            $table->decimal('fp_annee', 15, 2)->default(0);
            $table->decimal('fe_annee', 15, 2)->default(0);
            
            $table->integer('annee')->default(2026);
            
            $table->timestamps();
            
            $table->unique(['code_projet', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projet_investissements');
    }
};
