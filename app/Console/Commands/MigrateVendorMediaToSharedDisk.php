<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MigrateVendorMediaToSharedDisk extends Command
{
    protected $signature = 'media:migrate-vendor-media
        {--from=public : Source disk name to read existing files from}
        {--to= : Target disk name (defaults to filesystems.vendor_media_disk)}
        {--limit=0 : Max number of DB media rows to process (0 = all)}
        {--offset=0 : DB row offset for batched/resumable migration}
        {--only-missing : Skip files already present on target}
        {--dry-run : Print what would be copied without writing files}';

    protected $description = 'Copy vendor listing media files (banner + thumb variants) from local/public disk to a shared target disk (S3/R2).';

    public function handle(): int
    {
        if (!Schema::hasTable('vendor_listing_media')) {
            $this->error('Table vendor_listing_media does not exist.');
            return self::FAILURE;
        }

        $sourceDiskName = trim((string) $this->option('from'));
        $targetDiskName = trim((string) ($this->option('to') ?: config('filesystems.vendor_media_disk', 'public')));

        if ($sourceDiskName === '' || $targetDiskName === '') {
            $this->error('Both source and target disk names must be non-empty.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $onlyMissing = (bool) $this->option('only-missing');
        $limit = max(0, (int) $this->option('limit'));
        $offset = max(0, (int) $this->option('offset'));

        try {
            $sourceDisk = Storage::disk($sourceDiskName);
            $targetDisk = Storage::disk($targetDiskName);
            $localDisk = Storage::disk('local');
        } catch (\Throwable $exception) {
            $this->error('Unable to initialize configured disk(s): ' . $exception->getMessage());
            return self::FAILURE;
        }

        $query = DB::table('vendor_listing_media')
            ->orderBy('id')
            ->offset($offset)
            ->select(['id', 'file_path']);

        if ($limit > 0) {
            $query->limit($limit);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->warn('No media rows found for the requested limit/offset window.');
            return self::SUCCESS;
        }

        $this->line('Migration window rows: ' . $rows->count());
        $this->line('Source disk: ' . $sourceDiskName);
        $this->line('Target disk: ' . $targetDiskName);
        $this->line('Dry run: ' . ($dryRun ? 'yes' : 'no'));
        $this->line('Only missing on target: ' . ($onlyMissing ? 'yes' : 'no'));

        $processedRows = 0;
        $candidateFiles = 0;
        $copiedFiles = 0;
        $alreadyPresent = 0;
        $missingOnSource = 0;
        $failedFiles = 0;

        foreach ($rows as $row) {
            $processedRows++;
            $mediaId = (int) ($row->id ?? 0);
            $storedPath = trim((string) ($row->file_path ?? ''));
            if ($storedPath === '') {
                continue;
            }

            $paths = $this->candidatePathsForMedia((string) $storedPath);
            foreach ($paths as $path) {
                $candidateFiles++;

                if ($onlyMissing && $targetDisk->exists($path)) {
                    $alreadyPresent++;
                    continue;
                }

                $binary = $this->readFileFromSourceFallback($sourceDisk, $localDisk, $path);
                if ($binary === null) {
                    $missingOnSource++;
                    $this->warn('Missing source file for media ' . $mediaId . ': ' . $path);
                    continue;
                }

                if ($dryRun) {
                    $copiedFiles++;
                    continue;
                }

                try {
                    $written = $targetDisk->put($path, $binary);
                } catch (\Throwable $exception) {
                    $written = false;
                }

                if ($written) {
                    $copiedFiles++;
                } else {
                    $failedFiles++;
                    $this->error('Failed to copy media ' . $mediaId . ': ' . $path);
                }
            }

            if ($processedRows % 200 === 0) {
                $this->line('Processed rows: ' . $processedRows . ', copied candidates: ' . $copiedFiles);
            }
        }

        $this->newLine();
        $this->info('Vendor media migration completed.');
        $this->line('Rows processed: ' . $processedRows);
        $this->line('Candidate files considered: ' . $candidateFiles);
        $this->line('Files copied' . ($dryRun ? ' (would copy)' : '') . ': ' . $copiedFiles);
        $this->line('Files already present on target: ' . $alreadyPresent);
        $this->line('Files missing on source: ' . $missingOnSource);
        $this->line('Files failed to write: ' . $failedFiles);

        return $failedFiles > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function candidatePathsForMedia(string $storedPath): array
    {
        $primary = trim(str_replace('\\\\', '/', $storedPath));
        if ($primary === '') {
            return [];
        }

        $thumbPath = preg_replace('/-banner(\.[a-z0-9]+)$/i', '-thumb$1', $primary);
        $bannerPath = preg_replace('/-thumb(\.[a-z0-9]+)$/i', '-banner$1', $primary);

        $variants = [
            $primary,
            is_string($thumbPath) ? $thumbPath : '',
            is_string($bannerPath) ? $bannerPath : '',
        ];

        $unique = [];
        foreach ($variants as $path) {
            $path = ltrim(trim((string) $path), '/');
            if ($path === '') {
                continue;
            }
            $unique[$path] = true;
        }

        return array_keys($unique);
    }

    private function readFileFromSourceFallback($sourceDisk, $localDisk, string $path): ?string
    {
        try {
            if ($sourceDisk->exists($path)) {
                $binary = $sourceDisk->get($path);
                if (is_string($binary) && $binary !== '') {
                    return $binary;
                }
            }
        } catch (\Throwable $exception) {
            // Continue to fallback lookup.
        }

        $localCandidates = [
            $path,
            'public/' . ltrim($path, '/'),
        ];

        foreach ($localCandidates as $localPath) {
            try {
                if (!$localDisk->exists($localPath)) {
                    continue;
                }

                $binary = $localDisk->get($localPath);
                if (is_string($binary) && $binary !== '') {
                    return $binary;
                }
            } catch (\Throwable $exception) {
                continue;
            }
        }

        return null;
    }
}
