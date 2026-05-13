<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('investissements');

        Schema::create('investissements', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant_initial', 15, 2);
            $table->decimal('taux_actualisation', 9, 6);
            $table->decimal('van', 15, 2)->nullable();
            $table->decimal('tri', 15, 8)->nullable();
            $table->decimal('drci', 15, 6)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investissements');
    }
};
