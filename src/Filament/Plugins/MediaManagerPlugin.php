<?php

namespace Marzio\MediaManager\Filament\Plugins;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Marzio\MediaManager\Filament\Pages\MediaManager;

class MediaManagerPlugin implements Plugin {

    public function getId(): string {
        return 'media-manager';
    }

    public function register(Panel $panel): void {
        $panel->pages([MediaManager::class]);
    }

    public function boot(Panel $panel): void { }

    public static function make(): static {
        return new static();
    }
}