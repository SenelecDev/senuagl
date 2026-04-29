<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('titre', 10)->nullable()->after('matricule');
            $table->integer('enfants_21_ans')->default(0)->after('nombre_enfants');
            $table->integer('enfants_18_ans')->default(0)->after('enfants_21_ans');
            $table->boolean('part_trimf')->default(false)->after('enfants_18_ans');
            $table->boolean('part_ir')->default(false)->after('part_trimf');
            $table->string('num_ipres', 50)->nullable()->after('part_ir');
            $table->string('num_secu_social', 50)->nullable()->after('num_ipres');
            $table->string('num_identite', 50)->nullable()->after('nationalite');
            $table->string('organisation', 100)->nullable()->after('num_identite');
            $table->string('centre_de_responsabilite', 100)->nullable()->after('organisation');
            $table->string('lieu', 100)->nullable()->after('centre_de_responsabilite');
            $table->decimal('salaire_base', 12, 2)->nullable()->after('lieu');
            $table->string('mode_reglement', 50)->nullable()->after('salaire_base');
            $table->string('banque', 50)->nullable()->after('mode_reglement');
            $table->string('compte', 50)->nullable()->after('banque');
            $table->string('domiciliation', 100)->nullable()->after('compte');
            $table->string('rib', 50)->nullable()->after('domiciliation');
            $table->string('syndicat', 50)->nullable()->after('rib');
            $table->string('situation_affectation', 100)->nullable()->after('syndicat');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'titre',
                'enfants_21_ans',
                'enfants_18_ans',
                'part_trimf',
                'part_ir',
                'num_ipres',
                'num_secu_social',
                'num_identite',
                'organisation',
                'centre_de_responsabilite',
                'lieu',
                'salaire_base',
                'mode_reglement',
                'banque',
                'compte',
                'domiciliation',
                'rib',
                'syndicat',
                'situation_affectation',
            ]);
        });
    }
};