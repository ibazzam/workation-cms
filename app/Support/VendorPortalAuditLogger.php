<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VendorPortalAuditLogger
{
    public static function log(string $action, array $context = []): void
    {
        if (!Schema::hasTable('vendor_portal_audit_logs')) {
            return;
        }

        $actorUserId = session('portal_vendor_user_id');
        $actorName = session('portal_vendor_user');
        $actorEmail = session('portal_vendor_user_email');

        $targetUserId = $context['target_user_id'] ?? null;
        $targetIdentifier = $context['target_identifier'] ?? null;
        $severity = $context['severity'] ?? 'info';
        unset($context['target_user_id'], $context['target_identifier'], $context['severity']);

        try {
            DB::table('vendor_portal_audit_logs')->insert([
                'vendor_user_id' => is_numeric($actorUserId) ? (int) $actorUserId : null,
                'actor_name' => is_string($actorName) ? $actorName : null,
                'actor_email' => is_string($actorEmail) ? $actorEmail : null,
                'action' => $action,
                'severity' => is_string($severity) ? strtolower(trim($severity)) : 'info',
                'target_user_id' => is_numeric($targetUserId) ? (int) $targetUserId : null,
                'target_identifier' => is_string($targetIdentifier) ? $targetIdentifier : null,
                'details' => empty($context) ? null : json_encode($context, JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to write vendor portal audit log.', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
