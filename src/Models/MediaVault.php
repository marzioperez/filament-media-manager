<?php

namespace Marzio\MediaManager\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaVault extends Model implements HasMedia {

    use InteractsWithMedia;

    protected $table = 'media_vaults'; // o el nombre que usarás
    protected $guarded = [];

    public function registerMediaConversions(?Media $media = null): void {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 320, 320)
            ->format(Manipulations::FORMAT_WEBP)
            ->quality(80)
            ->queued();

        $this->addMediaConversion('webp')
            ->format(Manipulations::FORMAT_WEBP)
            ->quality(82)
            ->queued();
    }
}