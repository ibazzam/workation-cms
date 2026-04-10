<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateIslandClassification extends Command
{
    protected $signature = 'islands:populate-classification';
    protected $description = 'Backfill island_type for all islands based on is_inhabited flag and keyword signals';

    public function handle(): int
    {
        $this->info('Starting island_type backfill...');

        // Resort keyword signals
        $resortKeywords = ['resort', 'tourism', 'hotel', 'villa', 'tourist'];
        // Non-tourism lease keywords
        $leaseKeywords = ['agriculture', 'farming', 'aquaculture', 'mariculture', 'fish farm', 'lease'];

        // Get all islands
        $islands = DB::table('islands')->select('id', 'name', 'local_name', 'description', 'wikipedia_title', 'is_inhabited')->get();

        $updated = 0;
        $inhabited = 0;
        $resort = 0;
        $uninhabited = 0;

        $bar = $this->output->createProgressBar(count($islands));
        $bar->start();

        foreach ($islands as $island) {
            $islandType = 'uninhabited'; // Default

            // Rule 1: If is_inhabited = true, set to 'inhabited'
            if ($island->is_inhabited === 1 || $island->is_inhabited === true) {
                $islandType = 'inhabited';
                $inhabited++;
            } else {
                // Rule 2: Check for resort keywords in name, local_name, description, wikipedia_title
                $text = strtolower(($island->name ?? '') . ' ' . ($island->local_name ?? '') . ' ' . ($island->description ?? '') . ' ' . ($island->wikipedia_title ?? ''));
                
                $hasResortKeyword = false;
                foreach ($resortKeywords as $keyword) {
                    if (str_contains($text, strtolower($keyword))) {
                        $hasResortKeyword = true;
                        break;
                    }
                }

                if ($hasResortKeyword) {
                    $islandType = 'resort';
                    $resort++;
                } else {
                    // Rule 3: Otherwise set to 'uninhabited' (including agriculture/farming/aquaculture leases)
                    $islandType = 'uninhabited';
                    $uninhabited++;
                }
            }

            // Update the island
            DB::table('islands')->where('id', $island->id)->update(['island_type' => $islandType]);
            $updated++;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Island classification backfill complete!");
        $this->line("");
        $this->line("Summary:");
        $this->line("  Inhabited (local):        {$inhabited}");
        $this->line("  Resort:                    {$resort}");
        $this->line("  Uninhabited (leases):      {$uninhabited}");
        $this->line("  Total updated:             {$updated}");
        $this->line("");

        // Verify: rerun integrity checker to show locked counts
        $this->info("Running integrity verification...");
        $this->call('islands:integrity-report', ['--sample' => '0']);

        return 0;
    }
}
