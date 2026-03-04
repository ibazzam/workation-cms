<?php

namespace App\Console\Commands;

use App\Models\TransportProviderJob;
use App\Services\HttpTransportProviderAdapter;
use Illuminate\Console\Command;

class ProcessTransportProviderJobs extends Command
{
    protected $signature = 'transport:process-jobs {--limit=50}';
    protected $description = 'Process pending transport provider jobs (DLQ/outbound)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $jobs = TransportProviderJob::where(function ($q) {
            $q->where('status', 'pending')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'failed')->whereNotNull('next_attempt_at')->where('next_attempt_at', '<=', now());
              });
        })->orderBy('created_at')->limit($limit)->get();

        $adapter = new HttpTransportProviderAdapter();

        foreach ($jobs as $job) {
            $job->markProcessing();

            try {
                $payload = $job->payload ?? [];

                $result = match ($job->action) {
                    'hold_create' => $adapter->sendHoldCreate($payload),
                    'hold_confirm' => $adapter->sendHoldConfirm($payload),
                    'hold_release' => $adapter->sendHoldRelease($payload),
                    default => ['ok' => false, 'error' => 'unknown action'],
                };

                if (!empty($result['ok'])) {
                    $job->markCompleted();
                    $this->info("Job {$job->id} completed");
                } else {
                    $err = $result['error'] ?? 'provider error';
                    $job->markFailed($err);
                    $this->error("Job {$job->id} failed: {$err}");
                }
            } catch (\Throwable $e) {
                $job->markFailed($e->getMessage());
                $this->error("Job {$job->id} exception: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
