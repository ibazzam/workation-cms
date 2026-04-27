<?php

use App\Models\User;
use App\Models\BlogPost;
use App\Support\CheckoutPaymentRouter;
use App\Support\ReservationPricingPolicy;
use App\Support\ReservationSettlementCalculator;
use App\Support\UniformIconSystem;
use App\Support\VendorPropertyCompatibilityReader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

    // ─────────────────────────────────────────────────────────────
    // Islands & Atolls Directory
    // /islands              – full index with atoll filter chips
// /islands/atoll/{slug} – atoll-filtered index
// /islands/{slug}       – individual island page
// ─────────────────────────────────────────────────────────────

if (!function_exists('buildIslandsIndexPayload')) {
    function buildIslandsIndexPayload(?string $activeAtollSlug, ?string $activeIslandType = null): array
    {
        $islandTypeAliases = [
            'local' => 'inhabited',
        ];
        $allowedIslandTypes = ['inhabited', 'uninhabited', 'resort'];
        $activeIslandType = is_string($activeIslandType) ? strtolower(trim($activeIslandType)) : null;
        if ($activeIslandType !== null && array_key_exists($activeIslandType, $islandTypeAliases)) {
            $activeIslandType = $islandTypeAliases[$activeIslandType];
        }
        if ($activeIslandType !== null && !in_array($activeIslandType, $allowedIslandTypes, true)) {
            $activeIslandType = null;
        }

        $atolls  = \App\Models\Atoll::query()->orderedByCode()->get();
        $query   = \App\Models\Island::with('atoll')->orderBy('atoll_id')->orderBy('name');

        $activeAtoll = null;
        if ($activeAtollSlug !== null) {
            // Match by atoll slug column first, fall back to name-slug
            $activeAtoll = $atolls->first(fn ($a) =>
                ($a->slug ?? \Illuminate\Support\Str::slug($a->name)) === $activeAtollSlug
            );
            if ($activeAtoll) {
                $query->where('atoll_id', $activeAtoll->id);
            }
        }

        if ($activeIslandType !== null) {
            if ($activeIslandType === 'resort') {
                $query->where('island_type', 'resort');
            } elseif ($activeIslandType === 'inhabited') {
                $query->where(function ($typeQuery) {
                    $typeQuery->where('island_type', 'inhabited')
                        ->orWhere(function ($fallbackQuery) {
                            $fallbackQuery->whereNull('island_type')
                                ->where('is_inhabited', true);
                        });
                });
            } elseif ($activeIslandType === 'uninhabited') {
                $query->where(function ($typeQuery) {
                    $typeQuery->where('island_type', 'uninhabited')
                        ->orWhere(function ($fallbackQuery) {
                            $fallbackQuery->whereNull('island_type')
                                ->where('is_inhabited', false);
                        });
                });
            }
        }

        $islands = $query->get();

        // Group islands by atoll_id, then by island_type
        $groupedIslands = collect();
        foreach ($atolls as $atoll) {
            $atollIslands = $islands->where('atoll_id', $atoll->id);
            $groupedIslands->put($atoll->id, $atollIslands);
        }

        // Calculate stats from all islands (not filtered)
        $allIslands = \App\Models\Island::all();
        $islandStats = [
            'atolls_total' => (int) $atolls->count(),
            'islands_total' => (int) $allIslands->count(),
            'inhabited_total' => 0,
            'resort_total' => 0,
            'uninhabited_total' => 0,
        ];

        foreach ($allIslands as $island) {
            $typed = strtolower(trim((string) ($island->island_type ?? '')));
            if ($typed === 'resort') {
                $islandStats['resort_total']++;
            } elseif ($typed === 'inhabited') {
                $islandStats['inhabited_total']++;
            } elseif ($typed === 'uninhabited') {
                $islandStats['uninhabited_total']++;
            } elseif ((bool) ($island->is_inhabited ?? false)) {
                $islandStats['inhabited_total']++;
            } else {
                $islandStats['uninhabited_total']++;
            }
        }

        return [
            'islandStats' => $islandStats,
            'atolls' => $atolls,
            'groupedIslands' => $groupedIslands,
            'activeIslandType' => $activeIslandType,
            'activeAtollSlug' => $activeAtollSlug,
            'activeAtollName' => $activeAtoll ? ($activeAtoll->name ?? null) : null,
        ];
    }
}

// must be before /islands/{slug} wildcard
Route::get('/islands/atoll/{atoll}', function (Request $request, string $atoll) {
    return view('islands-index', buildIslandsIndexPayload($atoll, $request->query('type')));
});

Route::get('/islands', function (Request $request) {
    return view('islands-index', buildIslandsIndexPayload(null, $request->query('type')));
});

Route::get('/islands/{slug}', function (string $slug) {
    // Try slug column first; fall back to name-derived slug match
    $island = \App\Models\Island::with('atoll')
        ->where('slug', $slug)
        ->first();

    if (!$island) {
        // Attempt name-based slug match (no DB index, iterate only when slug not set)
        $island = \App\Models\Island::with('atoll')
            ->whereNull('slug')
            ->get()
            ->first(fn ($i) => \Illuminate\Support\Str::slug($i->name) === $slug);
    }

    if (!$island) {
        abort(404);
    }

    $relatedIslands = collect();
    if ($island->atoll_id) {
        $relatedIslands = \App\Models\Island::with('atoll')
            ->where('atoll_id', $island->atoll_id)
            ->where('id', '!=', $island->id)
            ->orderBy('name')
            ->limit(5)
            ->get();
    }

    return view('island-show', [
        'island'         => $island,
        'relatedIslands' => $relatedIslands,
    ]);
});