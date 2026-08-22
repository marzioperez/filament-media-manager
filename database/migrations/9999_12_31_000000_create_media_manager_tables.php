<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración única del Media Manager.
 *
 * Crea `media_vaults`, `media_folders` y añade `media_folder_id` a la tabla
 * `media` de Spatie Media Library.
 *
 * ── Sobre el nombre del fichero ────────────────────────────────────────────
 * El timestamp 9999_12_31 es deliberado. La columna `media_folder_id` se añade
 * a una tabla de terceros (`media`), que el proyecto anfitrión publica desde
 * Spatie con la fecha del día en que la publicó. Cualquier fecha fija que
 * usara este paquete podría quedar ANTES de esa migración, y entonces la
 * columna nunca se añadiría. Con 9999 esta migración corre siempre al final,
 * sin importar cuándo se instaló el paquete o cuándo se publicó Spatie.
 *
 * Al publicarla (`vendor:publish --tag=media-manager-migrations`) el paquete la
 * copia con la fecha actual, siguiendo la convención del proyecto anfitrión, y
 * deja de autocargar esta copia para no ejecutarla dos veces.
 *
 * ── Idempotencia ───────────────────────────────────────────────────────────
 * Cada bloque comprueba su estado antes de actuar, de modo que la migración es
 * segura sobre instalaciones que ya ejecutaron las tres migraciones antiguas
 * (`create_media_vaults_table`, `create_media_folders_table` y
 * `add_media_folder_id_to_media_table`).
 */
return new class extends Migration {

    public function up(): void {
        $this->createMediaVaultsTable();
        $this->createMediaFoldersTable();
        $this->addMediaFolderIdToMediaTable();
    }

    public function down(): void {
        $mediaTable = $this->mediaTable();

        if (Schema::hasTable($mediaTable) && Schema::hasColumn($mediaTable, 'media_folder_id')) {
            Schema::table($mediaTable, function (Blueprint $table) {
                // Se elimina primero la FK: algunos motores no permiten soltar
                // la columna mientras la restricción sigue viva.
                try {
                    $table->dropForeign(['media_folder_id']);
                } catch (\Throwable $e) {
                    // SQLite y otros motores pueden no tener la FK; continuar.
                }

                $table->dropColumn('media_folder_id');
            });
        }

        Schema::dropIfExists('media_folders');
        Schema::dropIfExists('media_vaults');
    }

    /**
     * Contenedor de medios por defecto (proyectos single-tenant).
     */
    private function createMediaVaultsTable(): void {
        if (Schema::hasTable('media_vaults')) {
            return;
        }

        Schema::create('media_vaults', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Carpetas del gestor. Cada fila se corresponde con un prefijo físico en el
     * disco de almacenamiento.
     */
    private function createMediaFoldersTable(): void {
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

            // Disco de almacenamiento. Se guarda para poder resolver la ruta
            // física aunque cambie la configuración por defecto.
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

    /**
     * Vincula cada media con su carpeta.
     */
    private function addMediaFolderIdToMediaTable(): void {
        $mediaTable = $this->mediaTable();

        if (! Schema::hasTable($mediaTable)) {
            throw new RuntimeException(sprintf(
                'Media Manager: no se encontró la tabla "%s" de Spatie Media Library. '
                . 'Publica y ejecuta primero sus migraciones:' . PHP_EOL
                . '  php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"' . PHP_EOL
                . '  php artisan migrate',
                $mediaTable
            ));
        }

        if (Schema::hasColumn($mediaTable, 'media_folder_id')) {
            return;
        }

        Schema::table($mediaTable, function (Blueprint $table) {
            $table->foreignId('media_folder_id')
                ->nullable()
                ->after('model_id')
                ->constrained('media_folders')
                ->nullOnDelete();
        });
    }

    private function mediaTable(): string {
        return config('media-library.table_name', 'media');
    }
};
