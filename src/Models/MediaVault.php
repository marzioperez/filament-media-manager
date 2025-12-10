<?php

namespace Marzio\MediaManager\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaVault extends Model implements HasMedia {

    use InteractsWithMedia;

    protected $table = 'media_vaults'; // o el nombre que usarás
    protected $guarded = [];

    public function registerMediaConversions(?Media $media = null): void {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 400, 400)->format('webp')->quality(80)->queued();
        $this->addMediaConversion('webp')->format('webp')->quality(82)->queued();
    }
}
