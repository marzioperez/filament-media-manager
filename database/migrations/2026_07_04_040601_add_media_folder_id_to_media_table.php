<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        $table = config('media-library.table_name', 'media');

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'media_folder_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->foreignId('media_folder_id')
                ->nullable()
                ->after('model_id')
                ->constrained('media_folders')
                ->nullOnDelete();
        });
    }

    public function down(): void {
        $table = config('media-library.table_name', 'media');

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'media_folder_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            // Elimina primero la FK para evitar errores en algunos motores.
            try {
                $table->dropForeign(['media_folder_id']);
            } catch (\Throwable $e) {
                // La FK puede no existir (SQLite u otros); continuar.
            }
            $table->dropColumn('media_folder_id');
        });
    }
};
