<?php

use App\Models\User;
use App\Models\BlogPost;
use App\Finance\LedgerWriter;
use App\Finance\RefundRouter;
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

Route::get('/customer', function (Request $request) {
    // ÔöÇÔöÇ Authentication gate: customer must be logged in ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
    if (!$request->session()->get('portal_customer_authenticated')) {
        return redirect('/portal/customer/login')->with('error', 'Please log in to access your bookings.');
    }


    $customerProperties = collect();
    $customerRoomsByProperty = collect();
    $propertyMediaByProperty = collect();
    $roomMediaByRoom = collect();

    $customerProperties = VendorPropertyCompatibilityReader::allActiveListings(240)
        ->sortByDesc(static function ($property) {
            $updatedAt = strtotime((string) ($property->updated_at ?? ''));
            return $updatedAt !== false ? $updatedAt : 0;
        })
        ->take(24)
        ->values();

    $propertyIds = $customerProperties->pluck('id')->map(static fn ($id) => (int) $id)->filter(static fn (int $id) => $id > 0)->values();

    if ($propertyIds->isNotEmpty() && Schema::hasTable('vendor_property_room_categories')) {
        $rooms = DB::table('vendor_property_room_categories')
            ->whereIn('vendor_property_id', $propertyIds->all())
            ->orderByDesc('updated_at')
            ->limit(400)
            ->get();

        $customerRoomsByProperty = $rooms->groupBy(static fn ($room) => (int) ($room->vendor_property_id ?? 0));
    }

    $roomIds = $customerRoomsByProperty
        ->flatten(1)
        ->pluck('id')
        ->map(static fn ($id) => (int) $id)
        ->filter(static fn (int $id) => $id > 0)
        ->values();

    if (Schema::hasTable('vendor_listing_media') && ($propertyIds->isNotEmpty() || $roomIds->isNotEmpty())) {
        $mediaQuery = DB::table('vendor_listing_media');

        $mediaQuery->where(function ($query) use ($propertyIds, $roomIds) {
            if ($propertyIds->isNotEmpty()) {
                $query->orWhere(function ($propertyQuery) use ($propertyIds) {
                    $propertyQuery
                        ->where('entity_type', 'property')
                        ->whereIn('entity_id', $propertyIds->all());
                });
            }

            if ($roomIds->isNotEmpty()) {
                $query->orWhere(function ($roomQuery) use ($roomIds) {
                    $roomQuery
                        ->where('entity_type', 'room')
                        ->whereIn('entity_id', $roomIds->all());
                });
            }
        });

        $mediaRows = $mediaQuery
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $propertyMediaByProperty = $mediaRows
            ->filter(static fn ($media) => strtolower((string) ($media->entity_type ?? '')) === 'property')
            ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));

        $roomMediaByRoom = $mediaRows
            ->filter(static fn ($media) => strtolower((string) ($media->entity_type ?? '')) === 'room')
            ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));
    }

    $customerProfile = [
        'name' => trim((string) session('portal_customer_user', 'Customer')),
        'email' => '',
        'member_since' => '-',
        'phone' => '',
        'dob' => '',
        'nationality' => '',
        'gender' => '',
        'preferred_language' => 'en',
        'address_line' => '',
        'address_atoll_id' => '',
        'address_island_id' => '',
    ];

    $customerUserId = session('portal_customer_user_id');
    if (is_string($customerUserId) || is_numeric($customerUserId)) {
        try {
            $customerRecord = \App\Models\Customer::query()->where('id', (string) $customerUserId)->first();
            if ($customerRecord) {
                $customerProfile['name'] = trim((string) ($customerRecord->name ?? $customerProfile['name']));
                $customerProfile['email'] = strtolower(trim((string) ($customerRecord->email ?? '')));

                $createdAtRaw = $customerRecord->createdAt ?? $customerRecord->created_at ?? null;
                if ($createdAtRaw) {
                    $customerProfile['member_since'] = Carbon::parse((string) $createdAtRaw)->format('M Y');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to load customer profile context for customer portal.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    if (is_string($customerUserId) || is_numeric($customerUserId)) {
        $profileMeta = cache()->get(customerProfileMetaCacheKey((string) $customerUserId));
        if (is_array($profileMeta)) {
            $customerProfile['phone'] = trim((string) ($profileMeta['phone'] ?? ''));
            $customerProfile['dob'] = trim((string) ($profileMeta['dob'] ?? ''));
            $customerProfile['nationality'] = trim((string) ($profileMeta['nationality'] ?? ''));
            $customerProfile['gender'] = trim((string) ($profileMeta['gender'] ?? ''));
            $customerProfile['preferred_language'] = trim((string) ($profileMeta['preferred_language'] ?? 'en'));
            $customerProfile['address_line'] = trim((string) ($profileMeta['address_line'] ?? ''));
            $customerProfile['address_atoll_id'] = trim((string) ($profileMeta['address_atoll_id'] ?? ''));
            $customerProfile['address_island_id'] = trim((string) ($profileMeta['address_island_id'] ?? ''));
        }
    }

    if ($customerProfile['email'] === '') {
        $customerProfile['email'] = strtolower(trim((string) session('portal_customer_email', '')));
    }

    $summary = [
        'upcoming_bookings' => 0,
        'completed_bookings' => 0,
        'receipts_available' => 0,
        'notification_state' => 'ACTIVE',
    ];

    $categoryMeta = [
        'accommodation'    => ['label' => 'Accommodation'],
        'marine_transport' => ['label' => 'Sea Transport & Ferries'],
        'land_transport'   => ['label' => 'Land Transport'],
        'excursion'        => ['label' => 'Excursions'],
        'remote_workspace' => ['label' => 'Remote Workspace'],
        'resort_day_visit' => ['label' => 'Resort Day Visit'],
        'restaurant'       => ['label' => 'Restaurant'],
        'vehicle_rental'   => ['label' => 'Vehicle Rental'],
        'water_sports'     => ['label' => 'Water Sports'],
        'sea_transport'    => ['label' => 'Sea Transport & Ferries'],
        'liveaboard'       => ['label' => 'Liveaboard / Safari'],
    ];

    $customerBookingsByCategory = collect(array_fill_keys(array_keys($categoryMeta), collect()));

    if (Schema::hasTable('vendor_reservations') && $customerProfile['email'] !== '') {
        $reservationRows = DB::table('vendor_reservations')
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customerProfile['email'])])
            ->orderByDesc('created_at')
            ->get(['id', 'vendor_property_id', 'start_at', 'end_at', 'status', 'payment_status', 'total_amount', 'currency', 'notes', 'created_at']);

        $reservationRows = $reservationRows
            ->filter(static function ($row): bool {
                $notes = json_decode((string) ($row->notes ?? ''), true);
                if (!is_array($notes)) {
                    return true;
                }

                return trim((string) ($notes['customer_deleted_at'] ?? '')) === '';
            })
            ->values();

        $latestRefundCaseByReservation = collect();
        $reservationIds = $reservationRows
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();
        if ($reservationIds->isNotEmpty() && Schema::hasTable('finance_refund_cases')) {
            $latestRefundCaseByReservation = DB::table('finance_refund_cases')
                ->whereIn('reservation_id', $reservationIds->all())
                ->orderByDesc('id')
                ->get([
                    'id',
                    'reservation_id',
                    'case_ref',
                    'status',
                    'reason_notes',
                    'resolution_notes',
                    'created_at',
                    'review_started_at',
                    'approved_at',
                    'completed_at',
                    'rejected_at',
                    'sla_due_at',
                    'sla_escalated_at',
                ])
                ->unique('reservation_id')
                ->keyBy('reservation_id');
        }

        $propertyNamesById = collect();
        $reservationServiceIds = collect();
        $reservationRoomIds = collect();
        $reservationPropertyIds = $reservationRows
            ->pluck('vendor_property_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $reservationRows->each(function ($row) use (&$reservationServiceIds, &$reservationRoomIds): void {
            $notes = json_decode((string) ($row->notes ?? ''), true);
            $notes = is_array($notes) ? $notes : [];

            $serviceId = (int) ($notes['service_id'] ?? $notes['vendor_service_id'] ?? 0);
            if ($serviceId > 0) {
                $reservationServiceIds->push($serviceId);
            }

            $roomId = (int) ($notes['room_id'] ?? $notes['vendor_room_category_id'] ?? 0);
            if ($roomId > 0) {
                $reservationRoomIds->push($roomId);
            }
        });

        $reservationServiceIds = $reservationServiceIds
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $reservationRoomIds = $reservationRoomIds
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($reservationPropertyIds->isNotEmpty()) {
            $propertyNamesById = $reservationPropertyIds
                ->map(static function (int $propertyId) {
                    $row = VendorPropertyCompatibilityReader::loadPropertyById($propertyId);
                    if (!$row) {
                        return null;
                    }

                    return (object) [
                        'id' => $propertyId,
                        'name' => (string) ($row->name ?? ''),
                        'listing_category' => (string) ($row->listing_category ?? ''),
                        'listing_details' => (string) ($row->listing_details ?? $row->details ?? ''),
                        'property_contact_name' => (string) ($row->property_contact_name ?? ''),
                        'property_contact_number' => (string) ($row->property_contact_number ?? ''),
                        'property_contact_email' => (string) ($row->property_contact_email ?? ''),
                        'contact_name' => (string) ($row->contact_name ?? ''),
                        'contact_number' => (string) ($row->contact_number ?? ''),
                        'contact_email' => (string) ($row->contact_email ?? ''),
                        'phone' => (string) ($row->phone ?? ''),
                    ];
                })
                ->filter()
                ->keyBy('id');
        }

        if (Schema::hasTable('vendor_listing_media') && ($reservationPropertyIds->isNotEmpty() || $reservationRoomIds->isNotEmpty() || $reservationServiceIds->isNotEmpty())) {
            $bookingMediaRows = DB::table('vendor_listing_media')
                ->where(function ($query) use ($reservationPropertyIds, $reservationRoomIds, $reservationServiceIds) {
                    $hasCondition = false;

                    if ($reservationPropertyIds->isNotEmpty()) {
                        $query->where(function ($inner) use ($reservationPropertyIds) {
                            $inner->whereIn('entity_type', ['property', 'transport', 'sea_transport', 'land_transport', 'liveaboard', 'marine_transport'])
                                ->whereIn('entity_id', $reservationPropertyIds->all());
                        });
                        $hasCondition = true;
                    }

                    if ($reservationRoomIds->isNotEmpty()) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $query->{$method}(function ($inner) use ($reservationRoomIds) {
                            $inner->where('entity_type', 'room')
                                ->whereIn('entity_id', $reservationRoomIds->all());
                        });
                        $hasCondition = true;
                    }

                    if ($reservationServiceIds->isNotEmpty()) {
                        $method = $hasCondition ? 'orWhere' : 'where';
                        $query->{$method}(function ($inner) use ($reservationServiceIds) {
                            $inner->whereIn('entity_type', ['service', 'equipment', 'rental_item'])
                                ->whereIn('entity_id', $reservationServiceIds->all());
                        });
                    }
                })
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->limit(1000)
                ->get();

            $propertyMediaByProperty = $bookingMediaRows
                ->filter(static function ($media): bool {
                    return strtolower(trim((string) ($media->entity_type ?? ''))) !== 'room';
                })
                ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));

            $roomMediaByRoom = $bookingMediaRows
                ->filter(static fn ($media) => strtolower(trim((string) ($media->entity_type ?? ''))) === 'room')
                ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));

            $serviceMediaByService = $bookingMediaRows
                ->filter(static fn ($media) => in_array(strtolower(trim((string) ($media->entity_type ?? ''))), ['service', 'equipment', 'rental_item'], true))
                ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));
        } else {
            $serviceMediaByService = collect();
        }

        $today = now()->startOfDay();
        $summary['upcoming_bookings'] = $reservationRows->filter(function ($row) use ($today) {
            $startAt = $row->start_at ? Carbon::parse((string) $row->start_at)->startOfDay() : null;
            return $startAt && $startAt->greaterThanOrEqualTo($today);
        })->count();

        $summary['completed_bookings'] = $reservationRows->filter(function ($row) use ($today) {
            $endAt = $row->end_at ? Carbon::parse((string) $row->end_at)->startOfDay() : null;
            return $endAt && $endAt->lessThan($today);
        })->count();

        $summary['receipts_available'] = $reservationRows->filter(function ($row) {
            return strtolower((string) ($row->payment_status ?? '')) === 'paid';
        })->count();

        $supportEmail = strtolower(trim((string) env('WORKATION_CUSTOMER_SUPPORT_EMAIL', 'support@workation.mv')));

        $resolveMediaUrl = static function ($media, string $variant = 'thumb'): string {
            if (!$media) {
                return '';
            }

            $mediaId = (int) ($media->id ?? 0);
            if ($mediaId > 0) {
                return '/media/vendor/' . $mediaId . '/' . $variant;
            }

            foreach (['public_url', 'url', 'media_url', 'file_url'] as $urlKey) {
                $candidate = trim((string) ($media->{$urlKey} ?? ''));
                if ($candidate !== '') {
                    return Str::startsWith($candidate, 'http://')
                        ? ('https://' . ltrim(substr($candidate, 7), '/'))
                        : $candidate;
                }
            }

            foreach (['path', 'storage_path', 'file_path', 'relative_path'] as $pathKey) {
                $rawPath = trim((string) ($media->{$pathKey} ?? ''));
                if ($rawPath === '') {
                    continue;
                }

                $resolved = function_exists('portalManagedMediaUrlFromPath')
                    ? portalManagedMediaUrlFromPath($rawPath)
                    : null;

                if (($resolved === null || trim((string) $resolved) === '') && function_exists('vendorMediaStorageUrlFromPath')) {
                    $resolved = vendorMediaStorageUrlFromPath($rawPath);
                }

                if (is_string($resolved) && trim($resolved) !== '') {
                    return trim($resolved);
                }
            }

            return '';
        };

        $pickPrimaryMedia = static function ($items) {
            return collect($items)
                ->sortByDesc(static fn ($media): int => (int) ($media->is_primary ?? 0))
                ->sortByDesc(static fn ($media): int => strtotime((string) ($media->created_at ?? '')) ?: 0)
                ->first();
        };

        $categorized = $reservationRows->map(function ($row) use ($propertyNamesById, $categoryMeta, $latestRefundCaseByReservation, $supportEmail, $roomMediaByRoom, $propertyMediaByProperty, $serviceMediaByService, $resolveMediaUrl, $pickPrimaryMedia) {
            $notes = json_decode((string) ($row->notes ?? ''), true);
            if (!is_array($notes)) {
                $notes = [];
            }

            $refundCase = $latestRefundCaseByReservation->get((int) ($row->id ?? 0));
            $refundStatus = strtolower(trim((string) (($refundCase->status ?? '') ?: ($notes['refund_status'] ?? ''))));
            $isOpenRefundTimeline = in_array($refundStatus, ['requested', 'under_review', 'approved'], true);
            $isRefundEscalated = ($refundCase && isset($refundCase->sla_escalated_at) && (string) $refundCase->sla_escalated_at !== '')
                || (($refundCase && isset($refundCase->sla_due_at) && (string) $refundCase->sla_due_at !== '' && now()->greaterThan($refundCase->sla_due_at)) && in_array($refundStatus, ['requested', 'under_review', 'approved'], true));

            $propertyId = (int) ($row->vendor_property_id ?? 0);
            $propertyRow = $propertyNamesById->get($propertyId);
            $roomId = (int) ($notes['room_id'] ?? $notes['vendor_room_category_id'] ?? 0);
            $serviceId = (int) ($notes['service_id'] ?? $notes['vendor_service_id'] ?? 0);

            $categoryKey = strtolower(trim((string) ($notes['category_key'] ?? '')));
            if ($categoryKey === '' && $propertyRow) {
                $categoryKey = strtolower(trim((string) ($propertyRow->listing_category ?? '')));
            }
            if ($categoryKey === '' && !empty($notes['room_id'])) {
                $categoryKey = 'accommodation';
            }
            // Normalise transport variants from search form / legacy data
            if ($categoryKey === 'transport' || $categoryKey === 'marine-transport' || $categoryKey === 'marine_transport') {
                $categoryKey = 'sea_transport';
            } elseif ($categoryKey === 'land-transport') {
                $categoryKey = 'land_transport';
            }
            if (!array_key_exists($categoryKey, $categoryMeta)) {
                $categoryKey = 'accommodation';
            }

            $serviceLabel = trim((string) ($notes['service_label'] ?? $notes['room_name'] ?? ''));
            if ($serviceLabel === '') {
                $serviceLabel = (string) ($categoryMeta[$categoryKey]['label'] ?? 'Service');
            }

            $propertyDetails = [];
            if ($propertyRow && isset($propertyRow->listing_details)) {
                $decodedListingDetails = json_decode((string) ($propertyRow->listing_details ?? ''), true);
                if (is_array($decodedListingDetails)) {
                    $propertyDetails = $decodedListingDetails;
                }
            }

            $statusLower = strtolower(trim((string) ($row->status ?? 'pending')));
            $paymentStatusLower = strtolower(trim((string) ($row->payment_status ?? 'unpaid')));
            $allowVendorContact = $paymentStatusLower === 'paid'
                || in_array($statusLower, ['confirmed', 'completed', 'cancel_requested', 'cancelled', 'canceled'], true);

            $vendorContactName = trim((string) (
                $notes['vendor_contact_name']
                ?? $propertyDetails['property_contact_name']
                ?? $propertyDetails['contact_name']
                ?? ($propertyRow->property_contact_name ?? $propertyRow->contact_name ?? '')
            ));

            $vendorContactNumber = trim((string) (
                $notes['vendor_contact_number']
                ?? $propertyDetails['property_contact_number']
                ?? $propertyDetails['contact_number']
                ?? $propertyDetails['phone']
                ?? ($propertyRow->property_contact_number ?? $propertyRow->contact_number ?? $propertyRow->phone ?? '')
            ));

            $vendorContactEmail = strtolower(trim((string) (
                $notes['vendor_contact_email']
                ?? $propertyDetails['property_contact_email']
                ?? $propertyDetails['contact_email']
                ?? ($propertyRow->property_contact_email ?? $propertyRow->contact_email ?? '')
            )));

            $thumbnailUrl = trim((string) ($notes['thumbnail_url'] ?? $notes['room_thumbnail_url'] ?? $notes['service_thumbnail_url'] ?? ''));
            if ($thumbnailUrl === '' && $roomId > 0) {
                $thumbnailUrl = $resolveMediaUrl($pickPrimaryMedia($roomMediaByRoom->get($roomId, collect())));
            }
            if ($thumbnailUrl === '' && $serviceId > 0) {
                $thumbnailUrl = $resolveMediaUrl($pickPrimaryMedia($serviceMediaByService->get($serviceId, collect())));
            }
            if ($thumbnailUrl === '' && $propertyId > 0) {
                $thumbnailUrl = $resolveMediaUrl($pickPrimaryMedia($propertyMediaByProperty->get($propertyId, collect())));
            }

            return [
                'id' => (int) ($row->id ?? 0),
                'category_key' => $categoryKey,
                'category_label' => (string) ($categoryMeta[$categoryKey]['label'] ?? 'Category'),
                'property_name' => trim((string) ($propertyRow->name ?? 'Property')),
                'service_label' => $serviceLabel,
                'thumbnail_url' => $thumbnailUrl,
                'start_at' => $row->start_at ? Carbon::parse((string) $row->start_at)->format('Y-m-d') : '-',
                'end_at' => $row->end_at ? Carbon::parse((string) $row->end_at)->format('Y-m-d') : '-',
                'status' => strtoupper(trim((string) ($row->status ?? 'pending'))),
                'payment_status' => strtoupper(trim((string) ($row->payment_status ?? 'unpaid'))),
                'refund_case_ref' => strtoupper(trim((string) ($refundCase->case_ref ?? ($notes['refund_case_ref'] ?? '')))),
                'refund_status' => strtoupper($refundStatus),
                'refund_open_timeline' => $isOpenRefundTimeline,
                'refund_requested_at' => $refundCase && isset($refundCase->created_at) ? (string) ($refundCase->created_at ?? '') : '',
                'refund_review_started_at' => $refundCase && isset($refundCase->review_started_at) ? (string) ($refundCase->review_started_at ?? '') : '',
                'refund_approved_at' => $refundCase && isset($refundCase->approved_at) ? (string) ($refundCase->approved_at ?? '') : '',
                'refund_completed_at' => $refundCase && isset($refundCase->completed_at) ? (string) ($refundCase->completed_at ?? '') : '',
                'refund_rejected_at' => $refundCase && isset($refundCase->rejected_at) ? (string) ($refundCase->rejected_at ?? '') : '',
                'refund_reason_notes' => trim((string) ($refundCase->reason_notes ?? '')),
                'refund_resolution_notes' => trim((string) ($refundCase->resolution_notes ?? '')),
                'refund_sla_due_at' => $refundCase && isset($refundCase->sla_due_at) ? (string) ($refundCase->sla_due_at ?? '') : '',
                'refund_sla_escalated' => $isRefundEscalated,
                'total_amount' => (float) ($row->total_amount ?? 0),
                'currency' => strtoupper(trim((string) ($row->currency ?? 'MVR'))),
                'created_at' => $row->created_at ? Carbon::parse((string) $row->created_at)->format('Y-m-d') : '-',
                'booking_contact_available' => $allowVendorContact,
                'vendor_display_name' => $allowVendorContact ? $vendorContactName : '',
                'support_email' => $supportEmail,
            ];
        });

        $customerBookingsByCategory = collect(array_keys($categoryMeta))
            ->mapWithKeys(function (string $categoryKey) use ($categorized) {
                return [$categoryKey => $categorized->where('category_key', $categoryKey)->values()];
            });
    }

    $allBookings = $customerBookingsByCategory->flatten(1)->sortByDesc('created_at')->values();
    $today = now()->startOfDay();
    $bookingStatusCounts = [
        'all'              => $allBookings->count(),
        'awaiting_payment' => $allBookings->filter(fn ($b) => strtolower((string) ($b['payment_status'] ?? '')) === 'unpaid' && !in_array(strtolower((string) ($b['status'] ?? '')), ['cancelled', 'canceled']))->count(),
        'upcoming'         => $allBookings->filter(fn ($b) => $b['start_at'] !== '-' && \Carbon\Carbon::parse((string) $b['start_at'])->startOfDay()->greaterThanOrEqualTo($today))->count(),
        'awaiting_review'  => $allBookings->filter(fn ($b) => !in_array(strtolower((string) ($b['status'] ?? '')), ['pending', 'cancelled', 'canceled']) && ($b['end_at'] === '-' || \Carbon\Carbon::parse((string) $b['end_at'])->isPast()))->count(),
    ];

    // ÔöÇÔöÇ Load in-platform message threads ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
    $reservationMessagesByReservation = collect();
    if (Schema::hasTable('reservation_messages') && $allBookings->isNotEmpty()) {
        $reservationIds = $allBookings->pluck('id')->filter(static fn ($id) => (int) $id > 0)->unique()->values()->all();
        if (!empty($reservationIds)) {
            $messageRows = DB::table('reservation_messages')
                ->whereIn('reservation_id', $reservationIds)
                ->orderBy('created_at')
                ->get(['id', 'reservation_id', 'sender_role', 'sender_display_name', 'message_text', 'is_flagged', 'created_at']);

            $reservationMessagesByReservation = $messageRows->groupBy(static fn ($m) => (int) ($m->reservation_id ?? 0));

            // Mark customer-sent messages as read for the customer view
            $unreadVendorMessageIds = $messageRows
                ->where('sender_role', 'vendor')
                ->where('customer_read', false)
                ->pluck('id')
                ->all();
            if (!empty($unreadVendorMessageIds)) {
                DB::table('reservation_messages')
                    ->whereIn('id', $unreadVendorMessageIds)
                    ->update(['customer_read' => true]);
            }
        }
    }

    // ── Load submitted reviews keyed by reservation_id ───────────────────────
    $submittedReviewsByReservation = collect();
    if (Schema::hasTable('vendor_property_reviews') && $allBookings->isNotEmpty()) {
        $reservationIds = $allBookings->pluck('id')->filter(static fn ($id) => (int) $id > 0)->unique()->values()->all();
        if (!empty($reservationIds)) {
            $submittedReviewsByReservation = DB::table('vendor_property_reviews')
                ->whereIn('reservation_id', $reservationIds)
                ->get(['id', 'reservation_id', 'rating', 'review_comment', 'status', 'created_at'])
                ->keyBy(static fn ($r) => (int) ($r->reservation_id ?? 0));
        }
    }

    return view('customer-portal', [
        'summary' => $summary,
        'customerProfile' => $customerProfile,
        'customerBookingsByCategory' => $customerBookingsByCategory,
        'allBookings' => $allBookings,
        'bookingStatusCounts' => $bookingStatusCounts,
        'bookingCategoryMeta' => $categoryMeta,
        'customerProperties' => $customerProperties,
        'customerRoomsByProperty' => $customerRoomsByProperty,
        'propertyMediaByProperty' => $propertyMediaByProperty,
        'roomMediaByRoom' => $roomMediaByRoom,
        'reservationMessagesByReservation' => $reservationMessagesByReservation,
        'submittedReviewsByReservation' => $submittedReviewsByReservation,
    ]);
});

Route::get('/customer/bookings/{reservation}/invoice.pdf', function (int $reservation) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors([
            'customer' => 'Sign in to download your invoice.',
        ]);
    }

    if (!Schema::hasTable('vendor_reservations')) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking records are not available right now.',
        ]);
    }

    $customerEmail = strtolower(trim((string) session('portal_customer_email', '')));
    if ($customerEmail === '') {
        return redirect('/customer')->withErrors([
            'customer' => 'Your member session is missing an email address. Please sign in again.',
        ]);
    }

    $reservationRow = DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->whereRaw('LOWER(customer_email) = ?', [$customerEmail])
        ->first();
    if (!$reservationRow) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking not found for this account.',
        ]);
    }

    try {
        $pdfBinary = workationRenderReservationPdfBinary($reservationRow, 'invoice');
    } catch (\Throwable $e) {
        return redirect('/customer')->withErrors([
            'customer' => 'Invoice could not be generated right now. Please try again shortly.',
        ]);
    }

    return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . workationReservationPdfFilename($reservationRow, 'invoice') . '"',
    ]);
});

Route::get('/customer/bookings/{reservation}/confirmation.pdf', function (int $reservation) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors([
            'customer' => 'Sign in to download your reservation confirmation.',
        ]);
    }

    if (!Schema::hasTable('vendor_reservations')) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking records are not available right now.',
        ]);
    }

    $customerEmail = strtolower(trim((string) session('portal_customer_email', '')));
    if ($customerEmail === '') {
        return redirect('/customer')->withErrors([
            'customer' => 'Your member session is missing an email address. Please sign in again.',
        ]);
    }

    $reservationRow = DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->whereRaw('LOWER(customer_email) = ?', [$customerEmail])
        ->first();
    if (!$reservationRow) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking not found for this account.',
        ]);
    }

    try {
        $pdfBinary = workationRenderReservationPdfBinary($reservationRow, 'confirmation');
    } catch (\Throwable $e) {
        return redirect('/customer')->withErrors([
            'customer' => 'Reservation confirmation could not be generated right now. Please try again shortly.',
        ]);
    }

    return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . workationReservationPdfFilename($reservationRow, 'confirmation') . '"',
    ]);
});

Route::post('/customer/bookings/{reservation}/cancel', function (Request $request, int $reservation) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors([
            'customer' => 'Sign in to manage your bookings.',
        ]);
    }

    if (!Schema::hasTable('vendor_reservations')) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking records are not available right now.',
        ]);
    }

    $customerEmail = strtolower(trim((string) session('portal_customer_email', '')));
    if ($customerEmail === '') {
        return redirect('/customer')->withErrors([
            'customer' => 'Your member session is missing an email address. Please sign in again.',
        ]);
    }

    $reservationRow = DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->whereRaw('LOWER(customer_email) = ?', [$customerEmail])
        ->first();

    if (!$reservationRow) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking not found for this account.',
        ]);
    }

    $currentStatus = strtolower(trim((string) ($reservationRow->status ?? 'pending')));
    if (in_array($currentStatus, ['cancelled', 'canceled'], true)) {
        return redirect('/customer')->with('portal_notice', 'This booking is already cancelled.');
    }

    $notes = json_decode((string) ($reservationRow->notes ?? ''), true);
    if (!is_array($notes)) {
        $notes = [];
    }

    $paymentStatus = strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid')));
    if ($paymentStatus === 'paid') {
        $refundCaseRef = '';
        if (Schema::hasTable('finance_refund_cases')) {
            $existingOpenRefundCaseRef = (string) (DB::table('finance_refund_cases')
                ->where('reservation_id', (int) ($reservationRow->id ?? 0))
                ->whereNotIn('status', ['completed', 'rejected'])
                ->value('case_ref') ?? '');

            if ($existingOpenRefundCaseRef !== '') {
                $refundCaseRef = $existingOpenRefundCaseRef;
            } else {
                try {
                    $refundRouter = new RefundRouter(new LedgerWriter());
                    $refundCaseRef = $refundRouter->openCase([
                        'reservation_id' => (int) ($reservationRow->id ?? 0),
                        'vendor_user_id' => (int) ($reservationRow->vendor_user_id ?? 0),
                        'customer_user_id' => isset($reservationRow->customer_user_id) ? (int) ($reservationRow->customer_user_id ?? 0) : null,
                        'refund_amount' => (float) ($reservationRow->payment_amount ?? $reservationRow->invoice_total_amount ?? $reservationRow->total_amount ?? 0),
                        'refund_type' => 'full',
                        'reason_code' => 'customer_cancellation',
                        'reason_notes' => 'Opened automatically from customer portal cancellation request for a paid booking.',
                        'requested_by_role' => 'CUSTOMER',
                        'requested_by_user_id' => null,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Unable to auto-open refund case from customer cancellation request.', [
                        'reservation_id' => (int) ($reservationRow->id ?? 0),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $notes['customer_cancel_requested_at'] = now()->toIso8601String();
        $notes['customer_cancel_requested_by'] = 'customer_portal';
        if ($refundCaseRef !== '') {
            $notes['refund_case_ref'] = $refundCaseRef;
        }

        DB::table('vendor_reservations')
            ->where('id', $reservation)
            ->update([
                'status' => 'cancel_requested',
                'notes' => json_encode($notes),
                'updated_at' => now(),
            ]);

        $updatedRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
        if ($updatedRow) {
            $bookingRef = '#' . (int) ($updatedRow->id ?? $reservation);
            $subject = 'Cancellation Request Submitted ÔÇô Booking ' . $bookingRef;
            $body = implode("\n", [
                'A customer submitted a paid-booking cancellation request.',
                '',
                'Booking Reference: ' . $bookingRef,
                'Customer: ' . trim((string) ($updatedRow->customer_name ?? 'Customer')),
                'Customer Email: ' . trim((string) ($updatedRow->customer_email ?? '')),
                'Current Status: CANCEL_REQUESTED',
                'Payment Status: ' . strtoupper(trim((string) ($updatedRow->payment_status ?? 'unpaid'))),
                $refundCaseRef !== '' ? ('Refund Case: ' . $refundCaseRef) : 'Refund Case: pending creation',
                '',
                'Please review refund eligibility and finalize cancellation workflow.',
                '',
                'Workation Team',
            ]);
            workationNotifyReservationStakeholders($updatedRow, $subject, $body);
        }

        if ($refundCaseRef !== '') {
            return redirect('/customer')->with('portal_notice', 'Cancellation request submitted. Refund case ' . $refundCaseRef . ' is now under review.');
        }

        return redirect('/customer')->with('portal_notice', 'Cancellation request submitted. Our team will confirm refund and cancellation details.');
    }

    $notes['customer_cancelled_at'] = now()->toIso8601String();
    $notes['customer_cancelled_by'] = 'customer_portal';

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->update([
            'status' => 'cancelled',
            'notes' => json_encode($notes),
            'updated_at' => now(),
        ]);

    $updatedRow = DB::table('vendor_reservations')->where('id', $reservation)->first();
    if ($updatedRow) {
        $bookingRef = '#' . (int) ($updatedRow->id ?? $reservation);
        $subject = 'Booking Cancelled ÔÇô Booking ' . $bookingRef;
        $body = implode("\n", [
            'A customer cancelled an unpaid booking.',
            '',
            'Booking Reference: ' . $bookingRef,
            'Customer: ' . trim((string) ($updatedRow->customer_name ?? 'Customer')),
            'Customer Email: ' . trim((string) ($updatedRow->customer_email ?? '')),
            'Current Status: CANCELLED',
            'Payment Status: ' . strtoupper(trim((string) ($updatedRow->payment_status ?? 'unpaid'))),
            '',
            'Workation Team',
        ]);
        workationNotifyReservationStakeholders($updatedRow, $subject, $body);
    }

    return redirect('/customer')->with('portal_notice', 'Booking cancelled successfully.');
});

Route::post('/customer/bookings/{reservation}/delete', function (Request $request, int $reservation) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors([
            'customer' => 'Sign in to manage your bookings.',
        ]);
    }

    if (!Schema::hasTable('vendor_reservations')) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking records are not available right now.',
        ]);
    }

    $customerEmail = strtolower(trim((string) session('portal_customer_email', '')));
    if ($customerEmail === '') {
        return redirect('/customer')->withErrors([
            'customer' => 'Your member session is missing an email address. Please sign in again.',
        ]);
    }

    $reservationRow = DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->whereRaw('LOWER(customer_email) = ?', [$customerEmail])
        ->first();

    if (!$reservationRow) {
        return redirect('/customer')->withErrors([
            'customer' => 'Booking not found for this account.',
        ]);
    }

    $paymentStatus = strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid')));
    $reservationStatus = strtolower(trim((string) ($reservationRow->status ?? 'pending')));
    $canDeleteFromPortal = $paymentStatus !== 'paid';

    if (!$canDeleteFromPortal) {
        return redirect('/customer')->withErrors([
            'customer' => 'Paid bookings cannot be deleted from the portal until refund/dispute flow is complete.',
        ]);
    }

    $notes = json_decode((string) ($reservationRow->notes ?? ''), true);
    if (!is_array($notes)) {
        $notes = [];
    }

    $notes['customer_deleted_at'] = now()->toIso8601String();
    $notes['customer_deleted_by'] = 'customer_portal';

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->update([
            'status' => in_array($reservationStatus, ['cancelled', 'canceled', 'failed', 'expired', 'rejected'], true)
                ? (string) ($reservationRow->status ?? 'cancelled')
                : 'cancelled',
            'notes' => json_encode($notes),
            'updated_at' => now(),
        ]);

    return redirect('/customer')->with('portal_notice', 'Booking removed from your portal list.');
});

// ÔöÇÔöÇ In-platform booking message: customer sends a message to the vendor ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
Route::post('/customer/bookings/{reservation}/messages', function (Request $request, int $reservation) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors(['customer' => 'Sign in to send messages.']);
    }

    $customerEmail = strtolower(trim((string) session('portal_customer_email', '')));
    if ($customerEmail === '') {
        return redirect('/customer')->withErrors(['customer' => 'Your session is missing an email. Please sign in again.']);
    }

    if (!Schema::hasTable('vendor_reservations') || !Schema::hasTable('reservation_messages')) {
        return redirect('/customer')->withErrors(['customer' => 'Messaging is not available right now.']);
    }

    $reservationRow = DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->whereRaw('LOWER(customer_email) = ?', [$customerEmail])
        ->first();

    if (!$reservationRow) {
        return redirect('/customer')->withErrors(['customer' => 'Booking not found for this account.']);
    }

    $reservationStatus = strtolower(trim((string) ($reservationRow->status ?? 'pending')));
    $paymentStatus = strtolower(trim((string) ($reservationRow->payment_status ?? 'unpaid')));
    $isEligible = $paymentStatus === 'paid'
        || in_array($reservationStatus, ['confirmed', 'completed', 'cancel_requested', 'cancelled', 'canceled'], true);

    if (!$isEligible) {
        return redirect('/customer')->withErrors(['customer' => 'You can only message vendors for paid, confirmed, or cancellation-related bookings.']);
    }

    $messageText = trim((string) ($request->input('message_text', '')));
    if ($messageText === '' || mb_strlen($messageText) > 2000) {
        return redirect('/customer#booking-' . $reservation)->withErrors(['customer' => 'Message must be between 1 and 2000 characters.']);
    }

    $filterResult = workationMessageContentFilter($messageText);
    if ($filterResult['blocked']) {
        return redirect('/customer#booking-' . $reservation)->withErrors(['customer' => $filterResult['reason']]);
    }

    $customerName = trim((string) session('portal_customer_name', $customerEmail));

    DB::table('reservation_messages')->insert([
        'reservation_id'     => $reservation,
        'sender_role'        => 'customer',
        'sender_user_id'     => null,
        'sender_display_name' => mb_substr($customerName, 0, 120),
        'message_text'       => $messageText,
        'is_flagged'         => false,
        'vendor_read'        => false,
        'customer_read'      => true,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    // Notify vendor
    $vendorUserId = (int) ($reservationRow->vendor_user_id ?? 0);
    if ($vendorUserId > 0 && Schema::hasTable('users')) {
        $vendorEmail = strtolower(trim((string) (DB::table('users')->where('id', $vendorUserId)->value('email') ?? '')));
        if ($vendorEmail !== '') {
            $reservationCode = 'RSV-' . str_pad((string) $reservation, 6, '0', STR_PAD_LEFT);
            workationSendVendorEmailSafe($vendorEmail, 'New message from guest ÔÇö ' . $reservationCode, implode("\n", [
                'You have received a new message from a guest.',
                '',
                'Reservation: ' . $reservationCode,
                'Guest: ' . $customerName,
                '',
                'Message:',
                $messageText,
                '',
                'Please log in to your Workation vendor portal to reply.',
                '',
                'Note: All communication must stay within the Workation platform.',
                '',
                'Workation Team',
            ]));
        }
    }

    return redirect('/customer#booking-' . $reservation)->with('portal_notice', 'Your message has been sent.');
});

// ÔöÇÔöÇ In-platform message: customer reports an off-platform request ÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇÔöÇ
Route::post('/customer/bookings/{reservation}/messages/{message}/report', function (Request $request, int $reservation, int $message) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors(['customer' => 'Sign in to report messages.']);
    }

    $customerEmail = strtolower(trim((string) session('portal_customer_email', '')));

    if (!Schema::hasTable('reservation_messages') || !Schema::hasTable('vendor_reservations')) {
        return redirect('/customer')->withErrors(['customer' => 'Reporting is not available right now.']);
    }

    // Verify ownership: the reservation must belong to this customer
    $ownsReservation = DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->whereRaw('LOWER(customer_email) = ?', [$customerEmail])
        ->exists();

    if (!$ownsReservation) {
        return redirect('/customer')->withErrors(['customer' => 'Booking not found for this account.']);
    }

    $messageRow = DB::table('reservation_messages')
        ->where('id', $message)
        ->where('reservation_id', $reservation)
        ->first();

    if (!$messageRow) {
        return redirect('/customer#booking-' . $reservation)->withErrors(['customer' => 'Message not found.']);
    }

    $reportReason = trim((string) ($request->input('report_reason', 'Off-platform contact or payment request reported by customer.')));
    $reportReason = mb_substr($reportReason !== '' ? $reportReason : 'Off-platform contact or payment request reported by customer.', 0, 500);

    DB::table('reservation_messages')
        ->where('id', $message)
        ->update([
            'is_flagged'     => true,
            'flagged_reason' => $reportReason,
            'flagged_pattern' => 'customer_report',
            'updated_at'     => now(),
        ]);

    // Alert admin
    $reservationCode = 'RSV-' . str_pad((string) $reservation, 6, '0', STR_PAD_LEFT);
    foreach (workationReservationAdminEmails() as $adminEmail) {
        if ($adminEmail !== '') {
            workationSendVendorEmailSafe($adminEmail, '[ACTION REQUIRED] Off-platform request reported ÔÇö ' . $reservationCode, implode("\n", [
                'A customer has reported an off-platform contact or payment request.',
                '',
                'Reservation: ' . $reservationCode,
                'Customer Email: ' . $customerEmail,
                'Message ID: ' . $message,
                'Report Reason: ' . $reportReason,
                '',
                'Please review the flagged message in the admin portal.',
                '',
                'Workation Team',
            ]));
        }
    }

    return redirect('/customer#booking-' . $reservation)->with('portal_notice', 'Thank you for reporting. Our team will review this message.');
});

// ── Customer: submit a property review ──────────────────────────────────────
Route::post('/customer/bookings/{reservation}/review', function (Request $request, int $reservation) {
    if (!(bool) session('portal_customer_authenticated', false)) {
        return redirect('/portal/customer/login')->withErrors(['customer' => 'Sign in to leave a review.']);
    }

    $customerEmail = strtolower(trim((string) session('portal_customer_email', '')));
    if ($customerEmail === '') {
        return redirect('/portal/customer/login')->withErrors(['customer' => 'Session expired. Please sign in again.']);
    }

    if (!Schema::hasTable('reservations') && !Schema::hasTable('vendor_reservations')) {
        return redirect('/customer')->withErrors(['customer' => 'Booking records are not available right now.']);
    }

    $reservationTable = Schema::hasTable('reservations') ? 'reservations' : 'vendor_reservations';

    // Load the reservation and verify it belongs to this customer
    $reservationRow = DB::table($reservationTable)
        ->where('id', $reservation)
        ->first();

    if (!$reservationRow) {
        return redirect('/customer')->withErrors(['customer' => 'Booking not found.']);
    }

    $reservationEmail = strtolower(trim((string) ($reservationRow->customer_email ?? $reservationRow->email ?? '')));
    if ($reservationEmail !== $customerEmail) {
        return redirect('/customer')->withErrors(['customer' => 'You are not authorised to review this booking.']);
    }

    // Booking must have ended
    $endAt = $reservationRow->end_at ?? $reservationRow->checkout_date ?? null;
    if (!$endAt || !\Carbon\Carbon::parse((string) $endAt)->isPast()) {
        return redirect('/customer#booking-' . $reservation)->withErrors(['customer' => 'You can only review a booking after the stay or service has ended.']);
    }

    // Booking must not be pending/cancelled
    $statusLower = strtolower(trim((string) ($reservationRow->status ?? 'pending')));
    if (in_array($statusLower, ['pending', 'cancelled', 'canceled'], true)) {
        return redirect('/customer#booking-' . $reservation)->withErrors(['customer' => 'Only confirmed or completed bookings can be reviewed.']);
    }

    // One review per reservation
    if (Schema::hasTable('vendor_property_reviews')) {
        $alreadyReviewed = DB::table('vendor_property_reviews')
            ->where('reservation_id', $reservation)
            ->exists();
        if ($alreadyReviewed) {
            return redirect('/customer#booking-' . $reservation)->with('portal_notice', 'You have already submitted a review for this booking.');
        }
    }

    // Validate input
    $validated = $request->validate([
        'rating'         => ['required', 'integer', 'min:1', 'max:5'],
        'review_comment' => ['required', 'string', 'min:20', 'max:2000'],
    ]);

    $rating  = (int) $validated['rating'];
    $comment = trim((string) $validated['review_comment']);

    $propertyId     = (int) ($reservationRow->vendor_property_id ?? 0);
    $customerUserId = (int) session('portal_customer_user_id', 0);
    $customerName   = trim((string) session('portal_customer_user', ''));

    if (Schema::hasTable('vendor_property_reviews')) {
        DB::table('vendor_property_reviews')->insert([
            'vendor_property_id' => $propertyId,
            'reservation_id'     => $reservation,
            'customer_user_id'   => $customerUserId > 0 ? $customerUserId : null,
            'customer_name'      => mb_substr($customerName, 0, 120),
            'rating'             => $rating,
            'review_comment'     => $comment,
            'status'             => 'approved',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Bust cached reviews for this property
        Cache::forget('property_profile:guest_reviews:v1:' . $propertyId);
    }

    return redirect('/customer#booking-' . $reservation)->with('portal_notice', 'Thank you! Your review has been submitted.');
});