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
        Schema::table('agents', function (Blueprint $table) {
            // Check if column exists before dropping to avoid errors if some don't exist
            $columns = [
                'situation_familiale',
                'nombre_enfants',
                'enfants_21_ans',
                'enfants_18_ans',
                'part_trimf',
                'part_ir',
                'num_ipres',
                'num_secu_social',
                'num_identite',
                'ethnie',
                'religion',
                'situation_affectation',
                'salaire_base',
                'mode_reglement',
                'banque',
                'compte',
                'domiciliation',
                'rib'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('agents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // For a safe rollback, we'd recreate the columns. 
            // In development, this is fine.
            if (!Schema::hasColumn('agents', 'situation_familiale')) {
                $table->string('situation_familiale', 30)->nullable();
                $table->integer('nombre_enfants')->default(0);
                $table->integer('enfants_21_ans')->default(0);
                $table->integer('enfants_18_ans')->default(0);
                $table->boolean('part_trimf')->default(false);
                $table->boolean('part_ir')->default(false);
                $table->string('num_ipres', 50)->nullable();
                $table->string('num_secu_social', 50)->nullable();
                $table->string('num_identite', 50)->nullable();
                $table->string('ethnie', 50)->nullable();
                $table->string('religion', 50)->nullable();
                $table->string('situation_affectation', 100)->nullable();
                $table->decimal('salaire_base', 12, 2)->nullable();
                $table->string('mode_reglement', 50)->nullable();
                $table->string('banque', 50)->nullable();
                $table->string('compte', 50)->nullable();
                $table->string('domiciliation', 100)->nullable();
                $table->string('rib', 50)->nullable();
            }
        });
    }
};
