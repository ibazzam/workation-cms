<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Client\Response;

class SyncMaldivesGeography extends Command
{
    protected $signature = 'geo:sync-maldives {--limit=0 : Limit island pages processed (0 = all)}';

    protected $description = 'Download Maldives atolls and islands (inhabited + uninhabited) and sync into atolls/islands tables.';

    public function handle(): int
    {
        if (!Schema::hasTable('atolls') || !Schema::hasTable('islands')) {
            $this->error('Required tables are missing. Run migrations first.');
            return self::FAILURE;
        }

        $this->info('Fetching Maldives atolls and islands from Wikipedia API...');

        $masterListWikitext = $this->fetchMasterListWikitext();
        if ($masterListWikitext === '') {
            $this->error('Could not fetch master Maldives island list from Wikipedia.');
            return self::FAILURE;
        }

        $parsed = $this->parseMasterIslandList($masterListWikitext);
        $atollNames = array_values(array_unique($parsed['atolls']));
        $islandRows = $parsed['islands'];

        if (empty($atollNames)) {
            $this->warn('No atoll records were fetched. Sync aborted to avoid partial data.');
            return self::FAILURE;
        }

        $atollNameToId = [];
        foreach ($atollNames as $name) {
            if ($name === '') {
                continue;
            }

            $existing = DB::table('atolls')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first(['id']);
            if ($existing) {
                DB::table('atolls')->where('id', (int) $existing->id)->update([
                    'wikipedia_title' => $name,
                    'source' => 'wikipedia',
                    'updated_at' => now(),
                ]);
                $atollId = (int) $existing->id;
            } else {
                $atollId = (int) DB::table('atolls')->insertGetId([
                    'name' => $name,
                    'wikipedia_title' => $name,
                    'source' => 'wikipedia',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $atollNameToId[$this->normalizeLookupKey($name)] = $atollId;
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $islandRows = array_slice($islandRows, 0, $limit);
        }

        $nameFrequency = [];
        foreach ($islandRows as $row) {
            $key = $this->normalizeLookupKey((string) ($row['name'] ?? ''));
            if ($key === '') {
                continue;
            }

            $nameFrequency[$key] = ($nameFrequency[$key] ?? 0) + 1;
        }

        $processed = 0;
        foreach ($islandRows as $row) {
            $islandName = $this->normalizeIslandName((string) ($row['name'] ?? ''));
            if ($islandName === '') {
                continue;
            }

            $nameKey = $this->normalizeLookupKey($islandName);
            if (($nameFrequency[$nameKey] ?? 0) > 1) {
                $islandName = trim($islandName . ' (' . ((string) ($row['atoll_name'] ?? '')) . ')');
            }

            $atollName = $this->normalizeAtollName((string) ($row['atoll_name'] ?? ''));
            $atollId = null;
            if ($atollName !== '') {
                $atollLookupKey = $this->normalizeLookupKey($atollName);
                $atollId = $atollNameToId[$atollLookupKey] ?? null;
                if ($atollId === null) {
                    $existingAtoll = DB::table('atolls')->whereRaw('LOWER(name) = ?', [mb_strtolower($atollName)])->first(['id']);
                    if ($existingAtoll) {
                        $atollId = (int) $existingAtoll->id;
                    } else {
                        $atollId = (int) DB::table('atolls')->insertGetId([
                            'name' => $atollName,
                            'wikipedia_title' => $atollName,
                            'source' => 'wikipedia',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $atollNameToId[$atollLookupKey] = $atollId;
                }
            }

            $existingIsland = DB::table('islands')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($islandName)])
                ->first(['id']);

            $payload = [
                'name' => $islandName,
                'atoll_id' => $atollId,
                'is_inhabited' => (bool) $row['is_inhabited'],
                'wikipedia_title' => (string) ($row['wikipedia_title'] ?? $islandName),
                'source' => 'wikipedia',
                'updated_at' => now(),
            ];

            if ($existingIsland) {
                DB::table('islands')->where('id', (int) $existingIsland->id)->update($payload);
            } else {
                DB::table('islands')->insert($payload + ['created_at' => now()]);
            }

            $processed++;
            if ($processed % 25 === 0) {
                $this->line('Processed islands: ' . $processed);
            }

            usleep(90000);
        }

        $this->info('Maldives geography sync completed.');
        $this->line('Atolls synced: ' . count($atollNameToId));
        $this->line('Islands synced: ' . $processed);

        return self::SUCCESS;
    }

    private function fetchMasterListWikitext(): string
    {
        $candidates = [
            'List of islands of the Maldives',
            'List_of_islands_of_the_Maldives',
        ];

        foreach ($candidates as $title) {
            $text = $this->fetchPageWikitext($title);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /**
     * @return array{atolls: array<int, string>, islands: array<int, array{name: string, wikipedia_title: string, atoll_name: string, is_inhabited: bool}>}
     */
    private function parseMasterIslandList(string $wikiText): array
    {
        $lines = preg_split('/\R/', $wikiText) ?: [];
        $atolls = [];
        $islands = [];
        $currentAtoll = null;
        $currentStatus = null;

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^==[^=]*\[\[([^\]|]*Atoll)(?:\|([^\]]+))?\]\][^=]*==$/i', $trimmed, $matches)) {
                $atollCandidate = trim((string) ((isset($matches[2]) && $matches[2] !== '') ? $matches[2] : ($matches[1] ?? '')));
                $currentAtoll = $this->normalizeAtollName($atollCandidate);
                if ($currentAtoll !== '') {
                    $atolls[] = $currentAtoll;
                }
                $currentStatus = null;
                continue;
            }

            if (preg_match('/^===\s*Inhabited Islands\s*===$/i', $trimmed)) {
                $currentStatus = true;
                continue;
            }

            if (preg_match('/^===\s*Uninhabited Islands\s*===$/i', $trimmed)) {
                $currentStatus = false;
                continue;
            }

            if (preg_match('/^===\s*Disappeared Islands\s*===$/i', $trimmed)) {
                $currentStatus = null;
                continue;
            }

            if ($currentAtoll === null || $currentStatus === null || !str_starts_with($trimmed, '*')) {
                continue;
            }

            $islandRaw = trim(ltrim($trimmed, '*'));
            $islandName = $this->parseIslandNameFromListItem($islandRaw);
            if ($islandName === '') {
                continue;
            }

            $islands[] = [
                'name' => $islandName,
                'wikipedia_title' => $islandName,
                'atoll_name' => $currentAtoll,
                'is_inhabited' => (bool) $currentStatus,
            ];
        }

        return [
            'atolls' => array_values(array_unique($atolls)),
            'islands' => $islands,
        ];
    }

    private function parseIslandNameFromListItem(string $listItem): string
    {
        if (preg_match('/\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/', $listItem, $matches)) {
            $candidate = trim((string) ((isset($matches[2]) && $matches[2] !== '') ? $matches[2] : ($matches[1] ?? '')));
            return $this->normalizeIslandName($candidate);
        }

        $candidate = preg_replace('/\(.*$/', '', $listItem) ?? $listItem;
        $candidate = trim(preg_replace('/\s+/', ' ', $candidate) ?? $candidate);

        return $this->normalizeIslandName($candidate);
    }

    /**
     * @return array<int, string>
     */
    private function fetchCategoryMembers(string $categoryTitle, array $types = ['page']): array
    {
        $members = [];
        $continueToken = null;

        do {
            $query = [
                'action' => 'query',
                'format' => 'json',
                'list' => 'categorymembers',
                'cmtitle' => 'Category:' . $categoryTitle,
                'cmlimit' => 'max',
                'cmtype' => implode('|', $types),
            ];

            if ($continueToken !== null) {
                $query['cmcontinue'] = $continueToken;
            }

            $response = $this->wikiRequest($query);
            if (!$response->ok()) {
                break;
            }

            $payload = $response->json();
            $rows = $payload['query']['categorymembers'] ?? [];
            foreach ($rows as $row) {
                $title = trim((string) ($row['title'] ?? ''));
                if (str_starts_with($title, 'Category:')) {
                    $title = trim(substr($title, strlen('Category:')));
                }
                if ($title !== '') {
                    $members[] = $title;
                }
            }

            $continueToken = $payload['continue']['cmcontinue'] ?? null;
        } while ($continueToken !== null);

        return array_values(array_unique($members));
    }

    /**
     * @param array<int, string> $categoryCandidates
     * @param array<int, string> $types
     * @return array<int, string>
     */
    private function fetchCategoryMembersFromCandidates(array $categoryCandidates, array $types = ['page']): array
    {
        foreach ($categoryCandidates as $categoryTitle) {
            $members = $this->fetchCategoryMembers($categoryTitle, $types);
            if (!empty($members)) {
                return $members;
            }
        }

        return [];
    }

    private function fetchPageWikitext(string $title): string
    {
        $response = $this->wikiRequest([
            'action' => 'parse',
            'format' => 'json',
            'page' => $title,
            'prop' => 'wikitext',
            'formatversion' => 2,
        ]);

        if (!$response->ok()) {
            return '';
        }

        return (string) ($response->json()['parse']['wikitext'] ?? '');
    }

    private function wikiRequest(array $query): Response
    {
        $headers = [
            'User-Agent' => 'WorkationCMSBot/1.0 (+https://workation-cms.local)',
            'Accept' => 'application/json',
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->acceptJson()
                ->get('https://en.wikipedia.org/w/api.php', $query);

            if ($response->ok()) {
                return $response;
            }

            $this->warn('Primary HTTPS request returned HTTP ' . $response->status() . '. Retrying with certificate verification disabled for this environment.');

            return Http::timeout(30)
                ->withOptions(['verify' => false])
                ->withHeaders($headers)
                ->acceptJson()
                ->get('https://en.wikipedia.org/w/api.php', $query);
        } catch (\Throwable $e) {
            $this->warn('Primary HTTPS request failed (' . $e->getMessage() . '). Retrying with certificate verification disabled for this environment.');

            return Http::timeout(30)
                ->withOptions(['verify' => false])
                ->withHeaders($headers)
                ->acceptJson()
                ->get('https://en.wikipedia.org/w/api.php', $query);
        }
    }

    private function normalizeAtollName(string $title): string
    {
        $name = trim(preg_replace('/\s+/', ' ', str_replace('_', ' ', $title)) ?? '');
        $name = preg_replace('/\s*\(.*\)$/', '', $name) ?? $name;
        if ($name === '') {
            return '';
        }

        if (!str_contains(mb_strtolower($name), 'atoll')) {
            $name .= ' Atoll';
        }

        return trim($name);
    }

    private function normalizeIslandName(string $title): string
    {
        $name = trim(preg_replace('/\s+/', ' ', str_replace('_', ' ', $title)) ?? '');
        $name = preg_replace('/\s*\(.*\)$/', '', $name) ?? $name;

        return trim($name);
    }

    private function extractAtollName(string $wikiText, string $fallbackTitle): string
    {
        $patterns = [
            '/\|\s*atoll\s*=\s*([^\n\|]+)/i',
            '/\|\s*administrative atoll\s*=\s*([^\n\|]+)/i',
            '/\|\s*admin atoll\s*=\s*([^\n\|]+)/i',
            '/\[\[([^\]]+Atoll)\]\]/i',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $wikiText, $matches)) {
                continue;
            }

            $candidate = trim((string) ($matches[1] ?? ''));
            if ($candidate === '') {
                continue;
            }

            $candidate = preg_replace('/\<.*?\>/', '', $candidate) ?? $candidate;
            $candidate = preg_replace('/\{\{.*?\}\}/', '', $candidate) ?? $candidate;
            $candidate = str_replace(['[[', ']]'], '', $candidate);
            $candidate = trim(preg_replace('/\s+/', ' ', $candidate) ?? $candidate);

            if ($candidate !== '') {
                return $this->normalizeAtollName($candidate);
            }
        }

        if (preg_match('/\(([^\)]+Atoll)\)/i', $fallbackTitle, $matches)) {
            return $this->normalizeAtollName((string) $matches[1]);
        }

        return '';
    }

    private function normalizeLookupKey(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim((string) $normalized);
    }
}
