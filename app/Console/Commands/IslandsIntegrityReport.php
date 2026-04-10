<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class IslandsIntegrityReport extends Command
{
    protected $signature = 'islands:integrity-report
        {--sample=30 : Number of mismatch rows to print in terminal}
        {--output-dir=storage/app/reports : Directory where JSON and CSV reports are written}
        {--resort-keywords=resort,tourism,hotel,villa : Comma-separated keywords that strongly indicate tourism resort classification}
        {--non-tourism-lease-keywords=agriculture,farming,aquaculture,mariculture,fish farm : Comma-separated keywords for non-tourism lease usage on uninhabited islands}';

    protected $description = 'Generate a one-time islands classification integrity report (missing island_type, invalid values, and is_inhabited conflicts).';

    public function handle(): int
    {
        if (!Schema::hasTable('islands')) {
            $this->error('The islands table is missing. Run migrations and data sync first.');
            return self::FAILURE;
        }

        if (!Schema::hasTable('atolls')) {
            $this->warn('The atolls table is missing; report will continue without atoll names.');
        }

        if (!Schema::hasColumn('islands', 'island_type')) {
            $this->error('The islands table does not include island_type. Add this column before running integrity checks.');
            return self::FAILURE;
        }

        if (!Schema::hasColumn('islands', 'is_inhabited')) {
            $this->error('The islands table does not include is_inhabited. Add this column before running integrity checks.');
            return self::FAILURE;
        }

        $rows = DB::table('islands')
            ->leftJoin('atolls', 'atolls.id', '=', 'islands.atoll_id')
            ->select([
                'islands.id',
                'islands.name',
                'islands.local_name',
                'islands.island_type',
                'islands.is_inhabited',
                'islands.description',
                'islands.wikipedia_title',
                'islands.source',
                'islands.atoll_id',
                DB::raw('atolls.name as atoll_name'),
            ])
            ->orderBy('islands.id')
            ->get();

        $resortKeywords = $this->parseKeywordsOption((string) $this->option('resort-keywords'));
        $nonTourismLeaseKeywords = $this->parseKeywordsOption((string) $this->option('non-tourism-lease-keywords'));

        $summary = [
            'atolls_total' => Schema::hasTable('atolls') ? (int) DB::table('atolls')->count() : 0,
            'islands_total' => (int) $rows->count(),
            'type_counts' => [
                'inhabited' => 0,
                'uninhabited' => 0,
                'resort' => 0,
                'missing' => 0,
                'invalid' => 0,
            ],
            'is_inhabited_counts' => [
                'true' => 0,
                'false' => 0,
                'null' => 0,
            ],
            'mismatch_counts' => [
                'missing_island_type' => 0,
                'invalid_island_type' => 0,
                'conflict_inhabited_type_vs_flag' => 0,
                'conflict_uninhabited_type_vs_flag' => 0,
                'resort_keyword_without_resort_type' => 0,
                'resort_type_with_non_tourism_lease_keyword' => 0,
            ],
            'advisory_counts' => [
                'uninhabited_non_tourism_lease_candidates' => 0,
            ],
            'keyword_signal_counts' => [
                'resort_keywords_detected' => 0,
                'non_tourism_lease_keywords_detected' => 0,
            ],
        ];

        $mismatches = [];

        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            $name = trim((string) ($row->name ?? ''));
            $atollName = trim((string) ($row->atoll_name ?? ''));
            $rawType = strtolower(trim((string) ($row->island_type ?? '')));
            $isInhabited = $this->normalizeNullableBool($row->is_inhabited ?? null);
            $haystack = strtolower(trim(implode(' ', [
                (string) ($row->name ?? ''),
                (string) ($row->local_name ?? ''),
                (string) ($row->wikipedia_title ?? ''),
                (string) ($row->description ?? ''),
            ])));

            $matchedResortKeywords = $this->findMatchedKeywords($haystack, $resortKeywords);
            $matchedNonTourismLeaseKeywords = $this->findMatchedKeywords($haystack, $nonTourismLeaseKeywords);

            if (count($matchedResortKeywords) > 0) {
                $summary['keyword_signal_counts']['resort_keywords_detected']++;
            }
            if (count($matchedNonTourismLeaseKeywords) > 0) {
                $summary['keyword_signal_counts']['non_tourism_lease_keywords_detected']++;
            }

            if ($rawType === '') {
                $summary['type_counts']['missing']++;
            } elseif (in_array($rawType, ['inhabited', 'uninhabited', 'resort'], true)) {
                $summary['type_counts'][$rawType]++;
            } else {
                $summary['type_counts']['invalid']++;
            }

            if ($isInhabited === true) {
                $summary['is_inhabited_counts']['true']++;
            } elseif ($isInhabited === false) {
                $summary['is_inhabited_counts']['false']++;
            } else {
                $summary['is_inhabited_counts']['null']++;
            }

            $reasons = [];

            if ($rawType === '') {
                $reasons[] = 'missing_island_type';
                $summary['mismatch_counts']['missing_island_type']++;
            } elseif (!in_array($rawType, ['inhabited', 'uninhabited', 'resort'], true)) {
                $reasons[] = 'invalid_island_type';
                $summary['mismatch_counts']['invalid_island_type']++;
            }

            if ($rawType === 'inhabited' && $isInhabited !== true) {
                $reasons[] = 'conflict_inhabited_type_vs_flag';
                $summary['mismatch_counts']['conflict_inhabited_type_vs_flag']++;
            }

            if ($rawType === 'uninhabited' && $isInhabited !== false) {
                $reasons[] = 'conflict_uninhabited_type_vs_flag';
                $summary['mismatch_counts']['conflict_uninhabited_type_vs_flag']++;
            }

            if ($rawType !== 'resort' && count($matchedResortKeywords) > 0) {
                $reasons[] = 'resort_keyword_without_resort_type';
                $summary['mismatch_counts']['resort_keyword_without_resort_type']++;
            }

            if ($rawType === 'resort' && count($matchedNonTourismLeaseKeywords) > 0) {
                $reasons[] = 'resort_type_with_non_tourism_lease_keyword';
                $summary['mismatch_counts']['resort_type_with_non_tourism_lease_keyword']++;
            }

            if ($rawType === 'uninhabited' && count($matchedNonTourismLeaseKeywords) > 0) {
                $summary['advisory_counts']['uninhabited_non_tourism_lease_candidates']++;
            }

            if (count($reasons) > 0) {
                $mismatches[] = [
                    'id' => $id,
                    'name' => $name,
                    'atoll_name' => $atollName,
                    'island_type' => $rawType,
                    'is_inhabited' => $isInhabited,
                    'matched_resort_keywords' => $matchedResortKeywords,
                    'matched_non_tourism_lease_keywords' => $matchedNonTourismLeaseKeywords,
                    'reasons' => $reasons,
                ];
            }
        }

        $summary['mismatches_total'] = count($mismatches);

        $this->newLine();
        $this->info('Islands classification integrity report');
        $this->line('Atolls total: ' . $summary['atolls_total']);
        $this->line('Islands total: ' . $summary['islands_total']);
        $this->line('Type counts: inhabited=' . $summary['type_counts']['inhabited']
            . ', uninhabited=' . $summary['type_counts']['uninhabited']
            . ', resort=' . $summary['type_counts']['resort']
            . ', missing=' . $summary['type_counts']['missing']
            . ', invalid=' . $summary['type_counts']['invalid']);
        $this->line('Flag counts: is_inhabited=true=' . $summary['is_inhabited_counts']['true']
            . ', false=' . $summary['is_inhabited_counts']['false']
            . ', null=' . $summary['is_inhabited_counts']['null']);
        $this->line('Keyword signals: resort=' . $summary['keyword_signal_counts']['resort_keywords_detected']
            . ', non_tourism_lease=' . $summary['keyword_signal_counts']['non_tourism_lease_keywords_detected']);
        $this->line('Advisory: uninhabited + non_tourism_lease_keywords=' . $summary['advisory_counts']['uninhabited_non_tourism_lease_candidates']);
        $this->line('Mismatches total: ' . $summary['mismatches_total']);

        $sampleSize = max(1, (int) $this->option('sample'));
        if ($summary['mismatches_total'] > 0) {
            $this->newLine();
            $this->warn('Sample mismatches (top ' . $sampleSize . '):');

            $tableRows = collect($mismatches)
                ->take($sampleSize)
                ->map(function (array $row): array {
                    return [
                        $row['id'],
                        $row['name'],
                        $row['atoll_name'] !== '' ? $row['atoll_name'] : '-',
                        $row['island_type'] !== '' ? $row['island_type'] : '(missing)',
                        $row['is_inhabited'] === null ? 'null' : ($row['is_inhabited'] ? 'true' : 'false'),
                        implode(', ', $row['reasons'])
                            . (count($row['matched_resort_keywords']) > 0 ? ' | resort_kw=' . implode('|', $row['matched_resort_keywords']) : '')
                            . (count($row['matched_non_tourism_lease_keywords']) > 0 ? ' | lease_kw=' . implode('|', $row['matched_non_tourism_lease_keywords']) : ''),
                    ];
                })
                ->all();

            $this->table(
                ['ID', 'Island', 'Atoll', 'island_type', 'is_inhabited', 'Reasons'],
                $tableRows
            );
        } else {
            $this->newLine();
            $this->info('No mismatches found. Classification data is consistent.');
        }

        $timestamp = now()->format('Ymd_His');
        $outputDir = (string) $this->option('output-dir');
        $absoluteOutputDir = $this->resolveOutputDir($outputDir);

        File::ensureDirectoryExists($absoluteOutputDir);

        $jsonPath = rtrim($absoluteOutputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'islands_integrity_report_' . $timestamp . '.json';
        $csvPath = rtrim($absoluteOutputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'islands_integrity_mismatches_' . $timestamp . '.csv';

        $reportPayload = [
            'generated_at' => now()->toIso8601String(),
            'summary' => $summary,
            'mismatches' => $mismatches,
        ];

        File::put($jsonPath, json_encode($reportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->writeMismatchesCsv($csvPath, $mismatches);

        $this->newLine();
        $this->info('Report files written:');
        $this->line('- JSON: ' . $jsonPath);
        $this->line('- CSV:  ' . $csvPath);

        return self::SUCCESS;
    }

    private function normalizeNullableBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((int) $value) === 1;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
            return false;
        }

        return null;
    }

    private function resolveOutputDir(string $outputDir): string
    {
        $trimmed = trim($outputDir);
        if ($trimmed === '') {
            return storage_path('app/reports');
        }

        if (str_starts_with($trimmed, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $trimmed) === 1) {
            return $trimmed;
        }

        return base_path($trimmed);
    }

    /**
        * @param array<int, array{id:int,name:string,atoll_name:string,island_type:string,is_inhabited:?bool,matched_resort_keywords:array<int,string>,matched_non_tourism_lease_keywords:array<int,string>,reasons:array<int,string>}> $mismatches
     */
    private function writeMismatchesCsv(string $path, array $mismatches): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to write CSV report at: ' . $path);
        }

        fputcsv($handle, ['id', 'name', 'atoll_name', 'island_type', 'is_inhabited', 'matched_resort_keywords', 'matched_non_tourism_lease_keywords', 'reasons']);

        foreach ($mismatches as $row) {
            fputcsv($handle, [
                $row['id'],
                $row['name'],
                $row['atoll_name'],
                $row['island_type'],
                $row['is_inhabited'] === null ? 'null' : ($row['is_inhabited'] ? 'true' : 'false'),
                implode('|', $row['matched_resort_keywords'] ?? []),
                implode('|', $row['matched_non_tourism_lease_keywords'] ?? []),
                implode('|', $row['reasons']),
            ]);
        }

        fclose($handle);
    }

    /**
     * @return array<int, string>
     */
    private function parseKeywordsOption(string $value): array
    {
        return collect(explode(',', $value))
            ->map(static fn (string $item): string => strtolower(trim($item)))
            ->filter(static fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $keywords
     * @return array<int, string>
     */
    private function findMatchedKeywords(string $haystack, array $keywords): array
    {
        if ($haystack === '' || count($keywords) === 0) {
            return [];
        }

        $matched = [];
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                $matched[] = $keyword;
            }
        }

        return array_values(array_unique($matched));
    }
}
