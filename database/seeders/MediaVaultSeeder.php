<?php

namespace Database\Seeders;

use Marzio\MediaManager\Models\MediaVault;
use Illuminate\Database\Seeder;

class MediaVaultSeeder extends Seeder {

    public function run(): void {
        MediaVault::firstOrCreate(['id' => 1], []);
    }
}
