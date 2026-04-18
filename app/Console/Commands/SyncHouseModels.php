<?php

namespace App\Console\Commands;

use App\Models\HouseModel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('house-models:sync')]
#[Description('Register GLB files in storage/app/public/models/ that have no DB record yet.')]
class SyncHouseModels extends Command
{
    public function handle(): int
    {
        $files = Storage::disk('public')->files('models');
        $glbs  = array_filter($files, fn ($f) => str_ends_with(strtolower($f), '.glb'));

        if (empty($glbs)) {
            $this->warn('No .glb files found in storage/app/public/models/');
            return self::SUCCESS;
        }

        $registered = 0;
        $skipped    = 0;

        foreach ($glbs as $path) {
            if (HouseModel::where('glb_path', $path)->exists()) {
                $this->line("  <comment>skip</comment>  {$path}  (already in DB)");
                $skipped++;
                continue;
            }

            $filename = pathinfo($path, PATHINFO_FILENAME);
            // "Apartmentbuilding" → "Apartment Building"
            $name = Str::title(preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $filename) ?? $filename);

            HouseModel::create([
                'name'       => $name,
                'house_type' => 'other',
                'glb_path'   => $path,
                'is_active'  => true,
                'sort_order' => 0,
            ]);

            $this->line("  <info>added</info>  {$path}  →  \"{$name}\"");
            $registered++;
        }

        $this->newLine();
        $this->info("Done. Registered: {$registered}  |  Skipped: {$skipped}");
        return self::SUCCESS;
    }
}
