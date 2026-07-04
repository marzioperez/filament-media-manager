<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        if (Schema::hasTable('media_folders')) {
            return;
        }

        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();

            // Nombre visible + slug seguro para el sistema de archivos.
            $table->string('name');
            $table->string('slug');

            // Ruta completa relativa a la raíz del disco (p.ej. "documentos/facturas").
            $table->string('path');

            // Disco de almacenamiento (normalmente S3). Se guarda para poder
            // resolver la ruta física aunque cambie la config por defecto.
            $table->string('disk')->nullable();

            // Jerarquía (carpetas anidadas).
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('media_folders')
                ->nullOnDelete();

            // Vault propietario (soporte multi-tenant, igual que Spatie media).
            // nullableMorphs() ya crea el índice (model_type, model_id).
            $table->nullableMorphs('model');

            $table->timestamps();

            // Evita nombres duplicados dentro de la misma carpeta y vault.
            $table->unique(['parent_id', 'slug', 'model_type', 'model_id'], 'media_folders_unique_slug');
        });
    }

    public function down(): void {
        Schema::dropIfExists('media_folders');
    }
};
