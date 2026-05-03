<?php

namespace Marzio\MediaManager\Vault;

use Illuminate\Database\Eloquent\Model;
use Marzio\MediaManager\Models\MediaVault;
use Spatie\MediaLibrary\HasMedia;

/**
 * Resolver del "vault" — el modelo que actúa como contenedor de las medias.
 *
 * Por defecto: MediaVault singleton (id = 1) — comportamiento histórico
 * del paquete, pensado para proyectos single-tenant.
 *
 * Override opcional: define en `config/media-manager.php`:
 *
 *     'vault' => [
 *         'resolver' => fn () => \Filament\Facades\Filament::getTenant(),
 *     ],
 *
 * El callable debe devolver un Model que implemente HasMedia. Útil para
 * proyectos multi-tenant donde cada tenant tiene su propia biblioteca.
 *
 * Si el callable devuelve `null` (p.ej. estamos fuera del contexto de un
 * tenant) cae al MediaVault default — el paquete nunca se rompe.
 */
class VaultResolver
{
    /**
     * Devuelve el modelo que actúa como vault para el contexto actual.
     */
    public static function model(): Model
    {
        $resolver = config('media-manager.vault.resolver');

        if (is_callable($resolver)) {
            $resolved = $resolver();

            if ($resolved instanceof Model && $resolved instanceof HasMedia) {
                return $resolved;
            }
        }

        return static::default();
    }

    /**
     * Vault por defecto: MediaVault singleton (id = 1).
     */
    public static function default(): MediaVault
    {
        return MediaVault::firstOrCreate(['id' => 1]);
    }

    /**
     * Tipo de morph (model_type) del vault actual.
     */
    public static function modelType(): string
    {
        return static::model()::class;
    }

    /**
     * Llave (model_id) del vault actual.
     */
    public static function modelId(): mixed
    {
        return static::model()->getKey();
    }
}
