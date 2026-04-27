<?php

namespace App\Console\Commands;

use App\Models\TransportHold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ReconcileTransportHolds extends Command
{
    protected $signature = 'transport:reconcile-holds {--limit=100}';
    protected $description = 'Reconcile and release expired transport holds';

    public function handle(): int
    {
        if (!Schema::hasTable('transport_holds')) {
            $this->warn('Skipping hold reconciliation: table transport_holds does not exist.');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');

        $expired = TransportHold::where('status', 'held')
            ->whereNotNull('ttl_expires_at')
            ->where('ttl_expires_at', '<=', now())
            ->limit($limit)
            ->get();

        foreach ($expired as $hold) {
            try {
                $hold->release();
                $this->info("Released hold {$hold->id}");
            } catch (\Throwable $e) {
                $this->error("Failed releasing hold {$hold->id}: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
