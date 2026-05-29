<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained('comptes')->cascadeOnDelete();
            $table->decimal('montant_engage', 15, 2);
            $table->date('date_engagement');
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index(['compte_id', 'date_engagement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};
