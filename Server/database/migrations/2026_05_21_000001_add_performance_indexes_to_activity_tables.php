<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->index(['user_id', 'statut', 'created_at'], 'idx_demandes_user_status_created');
            $table->index(['user_id', 'created_at'], 'idx_demandes_user_created');
            $table->index(['statut', 'date_validation'], 'idx_demandes_status_validation');
            $table->index(['date_debut', 'date_fin'], 'idx_demandes_date_range');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_notifications_user_created');
            $table->index(['user_id', 'lu', 'created_at'], 'idx_notifications_user_read_created');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_activity_logs_user_created');
            $table->index(['module', 'created_at'], 'idx_activity_logs_module_created');
            $table->index(['level', 'created_at'], 'idx_activity_logs_level_created');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_user_created');
            $table->dropIndex('idx_activity_logs_module_created');
            $table->dropIndex('idx_activity_logs_level_created');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_created');
            $table->dropIndex('idx_notifications_user_read_created');
        });

        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropIndex('idx_demandes_user_status_created');
            $table->dropIndex('idx_demandes_user_created');
            $table->dropIndex('idx_demandes_status_validation');
            $table->dropIndex('idx_demandes_date_range');
        });
    }
};
