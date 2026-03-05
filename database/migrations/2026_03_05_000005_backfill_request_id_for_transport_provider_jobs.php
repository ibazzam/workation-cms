<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_provider_jobs')) {
            return;
        }

        // Backfill existing rows where request_id is null
        $rows = DB::table('transport_provider_jobs')->whereNull('request_id')->get();

        foreach ($rows as $row) {
            $payload = [];
            if (! empty($row->payload)) {
                $payload = json_decode($row->payload, true) ?? [];
            }

            $rid = $payload['request_id'] ?? ($payload['meta']['request_id'] ?? (string) Str::uuid());

            $payload['request_id'] = $rid;

            DB::table('transport_provider_jobs')->where('id', $row->id)->update([
                'request_id' => $rid,
                'payload' => json_encode($payload),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transport_provider_jobs')) {
            return;
        }

        // Revert: remove request_id values added by this migration
        $rows = DB::table('transport_provider_jobs')->whereNotNull('request_id')->get();

        foreach ($rows as $row) {
            $payload = [];
            if (! empty($row->payload)) {
                $payload = json_decode($row->payload, true) ?? [];
            }

            if (isset($payload['request_id'])) {
                unset($payload['request_id']);
            }

            DB::table('transport_provider_jobs')->where('id', $row->id)->update([
                'request_id' => null,
                'payload' => json_encode($payload),
            ]);
        }
    }
};
