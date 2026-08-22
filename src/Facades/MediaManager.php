<?php

namespace Marzio\MediaManager\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Marzio\MediaManager\MediaManagerService;
use Marzio\MediaManager\Models\MediaFolder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @method static Media add(string $path, ?string $folder = null, string $disk = 'public', ?Model $vault = null, ?string $fileName = null, bool $unique = false, bool $preservingOriginal = true)
 * @method static Media addFromFile(string $absolutePath, ?string $folder = null, ?Model $vault = null, ?string $fileName = null, bool $unique = false, bool $preservingOriginal = true)
 * @method static string url(string $path, ?string $folder = null, string $disk = 'public', ?Model $vault = null, ?string $fileName = null, bool $unique = false, bool $preservingOriginal = true)
 * @method static MediaFolder|null folder(string $folder, ?Model $vault = null)
 * @method static MediaFolder|null findFolder(string $folder, ?Model $vault = null)
 *
 * @see MediaManagerService
 */
class MediaManager extends Facade {

    protected static function getFacadeAccessor(): string {
        return MediaManagerService::class;
    }
}
