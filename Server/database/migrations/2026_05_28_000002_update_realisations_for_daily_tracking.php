<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisations', function (Blueprint $table) {
            $table->date('date_realisation')->nullable()->after('montant_realise');
        });

        // Mise à jour des données existantes (par défaut au 1er du mois)
        DB::statement("UPDATE realisations SET date_realisation = date(annee || '-' || substr('0' || mois, -2, 2) || '-01')");

        Schema::table('realisations', function (Blueprint $table) {
            $table->dropColumn(['mois', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::table('realisations', function (Blueprint $table) {
            $table->unsignedTinyInteger('mois')->nullable();
            $table->unsignedSmallInteger('annee')->nullable();
        });

        DB::statement("UPDATE realisations SET mois = cast(strftime('%m', date_realisation) as integer), annee = cast(strftime('%Y', date_realisation) as integer)");

        Schema::table('realisations', function (Blueprint $table) {
            $table->dropColumn('date_realisation');
        });
    }
};
