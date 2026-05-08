<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'ethnie')) {
                $table->string('ethnie', 50)->nullable()->after('num_identite');
            }

            if (! Schema::hasColumn('agents', 'religion')) {
                $table->string('religion', 50)->nullable()->after('ethnie');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (Schema::hasColumn('agents', 'religion')) {
                $table->dropColumn('religion');
            }

            if (Schema::hasColumn('agents', 'ethnie')) {
                $table->dropColumn('ethnie');
            }
        });
    }
};

