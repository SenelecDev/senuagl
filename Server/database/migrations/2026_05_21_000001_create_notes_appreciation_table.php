<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes_appreciation', function (Blueprint $table) {
            $table->id();
            $table->string('matricule_agent');
            $table->year('annee');
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->foreign('matricule_agent')->references('matricule')->on('agents')->onDelete('cascade');
            $table->unique(['matricule_agent', 'annee']);
            $table->index('annee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes_appreciation');
    }
};
