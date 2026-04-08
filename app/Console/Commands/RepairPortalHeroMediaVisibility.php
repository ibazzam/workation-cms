<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RepairPortalHeroMediaVisibility extends Command
{
    protected $signature = 'media:repair-portal-hero-visibility
        {--disk= : Disk name (defaults to filesystems.portal_media_disk)}
        {--prefix=portal-admin/hero-images/ : Restrict to managed hero media path prefix}
        {--dry-run : Show what would be changed without writing}
        {--rewrite : Re-write object with public visibility and explicit ContentType}';

    protected $description = 'Repairs visibility/metadata for admin hero media so existing uploaded images render from the configured disk.';

    public function handle(): int
    {
        if (!Schema::hasTable('portal_finance_settings')) {
            $this->error('Table portal_finance_settings does not exist.');
            return self::FAILURE;
        }

        $diskName = trim((string) ($this->option('disk') ?: portalManagedMediaDiskName()));
        $prefix = ltrim(trim((string) $this->option('prefix')), '/');
        $dryRun = (bool) $this->option('dry-run');
        $rewrite = (bool) $this->option('rewrite');

        if ($diskName === '') {
            $this->error('Disk name cannot be empty.');
            return self::FAILURE;
        }

        if ($prefix === '') {
            $this->error('Prefix cannot be empty.');
            return self::FAILURE;
        }

        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable $exception) {
            $this->error('Unable to initialize disk [' . $diskName . ']: ' . $exception->getMessage());
            return self::FAILURE;
        }

        $rows = DB::table('portal_finance_settings')
            ->where(function ($query) {
                $query->where('setting_key', 'home_hero_image_url')
                    ->orWhere('setting_key', 'like', 'catalog_hero_image_%');
            })
            ->get(['setting_key', 'value_string']);

        if ($rows->isEmpty()) {
            $this->warn('No hero media settings rows found.');
            return self::SUCCESS;
        }

        $pathMap = [];
        foreach ($rows as $row) {
            $settingKey = (string) ($row->setting_key ?? '');
            $storedValue = (string) ($row->value_string ?? '');
            $relativePath = portalManagedMediaRelativePath($storedValue);
            if ($relativePath === null) {
                continue;
            }

            $normalizedPath = ltrim(trim($relativePath), '/');
            if ($normalizedPath === '' || !str_starts_with($normalizedPath, $prefix)) {
                continue;
            }

            if (!array_key_exists($normalizedPath, $pathMap)) {
                $pathMap[$normalizedPath] = [];
            }
            $pathMap[$normalizedPath][] = $settingKey;
        }

        if (empty($pathMap)) {
            $this->warn('No managed hero media paths were found in settings.');
            return self::SUCCESS;
        }

        $this->line('Disk: ' . $diskName);
        $this->line('Prefix: ' . $prefix);
        $this->line('Managed hero paths referenced in settings: ' . count($pathMap));
        $this->line('Dry run: ' . ($dryRun ? 'yes' : 'no'));
        $this->line('Rewrite objects: ' . ($rewrite ? 'yes' : 'no'));

        $checked = 0;
        $missing = 0;
        $fixed = 0;
        $failed = 0;

        foreach ($pathMap as $path => $settingKeys) {
            $checked++;

            try {
                $exists = $disk->exists($path);
            } catch (\Throwable $exception) {
                $exists = false;
            }

            if (!$exists) {
                $missing++;
                $this->warn('Missing object: ' . $path . ' (settings: ' . implode(', ', $settingKeys) . ')');
                continue;
            }

            if ($dryRun) {
                $this->line('Would repair: ' . $path);
                $fixed++;
                continue;
            }

            $updated = false;
            try {
                $disk->setVisibility($path, 'public');
                $updated = true;
            } catch (\Throwable $exception) {
                $updated = false;
            }

            if ($rewrite) {
                $rewritten = $this->rewriteObjectWithPublicVisibility($disk, $path);
                $updated = $updated || $rewritten;
            }

            if ($updated) {
                $fixed++;
                $this->info('Repaired: ' . $path);
            } else {
                $failed++;
                $this->error('Failed to repair: ' . $path);
            }
        }

        $this->newLine();
        $this->info('Portal hero media visibility repair completed.');
        $this->line('Checked: ' . $checked);
        $this->line('Fixed' . ($dryRun ? ' (would fix)' : '') . ': ' . $fixed);
        $this->line('Missing objects: ' . $missing);
        $this->line('Failed: ' . $failed);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function rewriteObjectWithPublicVisibility($disk, string $path): bool
    {
        try {
            $binary = $disk->get($path);
            if (!is_string($binary) || $binary === '') {
                return false;
            }

            $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            $contentType = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };

            return (bool) $disk->put($path, $binary, [
                'visibility' => 'public',
                'ContentType' => $contentType,
            ]);
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
