<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avancements', function (Blueprint $table) {
            $table->dropForeign(['matricule_agent']);
            $table->foreign('matricule_agent')
                ->references('matricule')
                ->on('agents')
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('avancements', function (Blueprint $table) {
            $table->dropForeign(['matricule_agent']);
            $table->foreign('matricule_agent')
                ->references('matricule')
                ->on('agents');
        });
    }
};
