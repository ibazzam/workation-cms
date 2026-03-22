<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

Route::get('/vendor', function () {
    if (!session()->get('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;

    $vendorProperties = collect();
    $vendorServices = collect();
    $vendorAvailability = collect();
    $vendorReservations = collect();
    $vendorPricingRules = collect();
    $vendorBilling = null;

    if ($vendorUserId > 0) {
        if (Schema::hasTable('vendor_properties')) {
            $vendorProperties = DB::table('vendor_properties')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        if (Schema::hasTable('vendor_services')) {
            $vendorServices = DB::table('vendor_services')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(250)
                ->get();
        }

        if (Schema::hasTable('vendor_availability_slots')) {
            $vendorAvailability = DB::table('vendor_availability_slots')
                ->where('vendor_user_id', $vendorUserId)
                ->orderBy('slot_date')
                ->limit(365)
                ->get();
        }

        if (Schema::hasTable('vendor_reservations')) {
            $vendorReservations = DB::table('vendor_reservations')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('start_at')
                ->limit(300)
                ->get();
        }

        if (Schema::hasTable('vendor_pricing_rules')) {
            $vendorPricingRules = DB::table('vendor_pricing_rules')
                ->where('vendor_user_id', $vendorUserId)
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get();
        }

        if (Schema::hasTable('vendor_billing_details')) {
            $vendorBilling = DB::table('vendor_billing_details')
                ->where('vendor_user_id', $vendorUserId)
                ->first();
        }
    }

    return view('vendor-portal', [
        'apiBase' => workationApiBase(),
        'portalUser' => session('portal_vendor_user', 'Vendor'),
        'vendorProfile' => [
            'name' => $vendorUser instanceof User ? (string) $vendorUser->name : (string) session('portal_vendor_user', 'Vendor'),
            'email' => $vendorUser instanceof User ? (string) $vendorUser->email : '',
            'phone' => ($vendorUser instanceof User && Schema::hasColumn('users', 'phone')) ? (string) ($vendorUser->phone ?? '') : '',
            'vendor_id' => $vendorUser instanceof User ? (string) ($vendorUser->portal_vendor_id ?? '') : '',
        ],
        'vendorProperties' => $vendorProperties,
        'vendorServices' => $vendorServices,
        'vendorAvailability' => $vendorAvailability,
        'vendorReservations' => $vendorReservations,
        'vendorPricingRules' => $vendorPricingRules,
        'vendorBilling' => $vendorBilling,
    ]);
});

Route::post('/portal/vendor/profile/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || normalizePortalRoleValue((string) $vendorUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your vendor account. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'display_name' => ['required', 'string', 'max:120'],
        'contact_phone' => ['nullable', 'string', 'max:40'],
    ]);

    $vendorUser->name = trim((string) $validated['display_name']);
    if (Schema::hasColumn('users', 'phone')) {
        $vendorUser->phone = vendorNormalizePhoneNumber((string) ($validated['contact_phone'] ?? ''));
    }
    $vendorUser->save();

    session([
        'portal_vendor_user' => $vendorUser->name,
    ]);

    return back()->with('portal_notice', 'Profile settings updated successfully.');
});

Route::post('/portal/vendor/properties/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_properties')) {
        return back()->withErrors(['profile' => 'Vendor properties table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'property_type' => ['required', Rule::in(['property', 'service'])],
        'location' => ['nullable', 'string', 'max:190'],
        'description' => ['nullable', 'string', 'max:3000'],
        'base_price' => ['nullable', 'numeric', 'min:0'],
        'max_guests' => ['nullable', 'integer', 'min:1', 'max:10000'],
    ]);

    DB::table('vendor_properties')->insert([
        'vendor_user_id' => $vendorUserId,
        'name' => trim((string) $validated['name']),
        'property_type' => (string) $validated['property_type'],
        'location' => trim((string) ($validated['location'] ?? '')),
        'description' => trim((string) ($validated['description'] ?? '')),
        'status' => 'active',
        'base_price' => (float) ($validated['base_price'] ?? 0),
        'currency' => 'MVR',
        'max_guests' => (int) ($validated['max_guests'] ?? 1),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('portal_notice', 'Property/service listing added.');
});

Route::post('/portal/vendor/services/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_services')) {
        return back()->withErrors(['profile' => 'Vendor services table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'category' => ['required', 'string', 'max:120'],
        'description' => ['nullable', 'string', 'max:3000'],
        'price' => ['required', 'numeric', 'min:0'],
        'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
        'property_id' => ['nullable', 'integer'],
    ]);

    DB::table('vendor_services')->insert([
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['property_id'] ?? null) ? (int) $validated['property_id'] : null,
        'name' => trim((string) $validated['name']),
        'category' => trim((string) $validated['category']),
        'description' => trim((string) ($validated['description'] ?? '')),
        'duration_minutes' => (int) ($validated['duration_minutes'] ?? 0),
        'price' => (float) $validated['price'],
        'currency' => 'MVR',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('portal_notice', 'Service added successfully.');
});

Route::post('/portal/vendor/availability/save', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_availability_slots')) {
        return back()->withErrors(['profile' => 'Vendor availability table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'slot_date' => ['required', 'date'],
        'inventory' => ['required', 'integer', 'min:0', 'max:100000'],
        'is_closed' => ['nullable', 'boolean'],
        'vendor_property_id' => ['nullable', 'integer'],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    DB::table('vendor_availability_slots')->updateOrInsert(
        [
            'vendor_user_id' => $vendorUserId,
            'vendor_property_id' => filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null,
            'slot_date' => (string) $validated['slot_date'],
        ],
        [
            'inventory' => (int) $validated['inventory'],
            'is_closed' => (bool) ($validated['is_closed'] ?? false),
            'notes' => trim((string) ($validated['notes'] ?? '')),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    return back()->with('portal_notice', 'Availability updated.');
});

Route::post('/portal/vendor/reservations/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_reservations')) {
        return back()->withErrors(['profile' => 'Vendor reservations table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'customer_name' => ['required', 'string', 'max:160'],
        'customer_email' => ['required', 'email', 'max:190'],
        'start_at' => ['required', 'date'],
        'end_at' => ['required', 'date', 'after_or_equal:start_at'],
        'guests' => ['required', 'integer', 'min:1', 'max:10000'],
        'total_amount' => ['required', 'numeric', 'min:0'],
        'vendor_property_id' => ['nullable', 'integer'],
        'vendor_service_id' => ['nullable', 'integer'],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    DB::table('vendor_reservations')->insert([
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null,
        'vendor_service_id' => filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null,
        'customer_name' => trim((string) $validated['customer_name']),
        'customer_email' => strtolower(trim((string) $validated['customer_email'])),
        'start_at' => (string) $validated['start_at'],
        'end_at' => (string) $validated['end_at'],
        'guests' => (int) $validated['guests'],
        'total_amount' => (float) $validated['total_amount'],
        'currency' => 'MVR',
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'notes' => trim((string) ($validated['notes'] ?? '')),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('portal_notice', 'Reservation added.');
});

Route::post('/portal/vendor/reservations/{reservation}/status', function (Request $request, int $reservation) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_reservations')) {
        return back()->withErrors(['profile' => 'Vendor reservations table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
        'payment_status' => ['required', Rule::in(['unpaid', 'partially_paid', 'paid', 'refunded'])],
    ]);

    DB::table('vendor_reservations')
        ->where('id', $reservation)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'status' => (string) $validated['status'],
            'payment_status' => (string) $validated['payment_status'],
            'updated_at' => now(),
        ]);

    return back()->with('portal_notice', 'Reservation status updated.');
});

Route::post('/portal/vendor/pricing/create', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_pricing_rules')) {
        return back()->withErrors(['profile' => 'Vendor pricing rules table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:160'],
        'rule_type' => ['required', Rule::in(['flat', 'percent', 'nightly', 'weekend_markup'])],
        'value' => ['required', 'numeric', 'min:0'],
        'starts_on' => ['nullable', 'date'],
        'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        'vendor_property_id' => ['nullable', 'integer'],
        'vendor_service_id' => ['nullable', 'integer'],
    ]);

    DB::table('vendor_pricing_rules')->insert([
        'vendor_user_id' => $vendorUserId,
        'vendor_property_id' => filled($validated['vendor_property_id'] ?? null) ? (int) $validated['vendor_property_id'] : null,
        'vendor_service_id' => filled($validated['vendor_service_id'] ?? null) ? (int) $validated['vendor_service_id'] : null,
        'name' => trim((string) $validated['name']),
        'rule_type' => (string) $validated['rule_type'],
        'value' => (float) $validated['value'],
        'starts_on' => $validated['starts_on'] ?? null,
        'ends_on' => $validated['ends_on'] ?? null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('portal_notice', 'Pricing rule saved.');
});

Route::post('/portal/vendor/billing/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }
    if (!Schema::hasTable('vendor_billing_details')) {
        return back()->withErrors(['profile' => 'Vendor billing details table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'business_name' => ['required', 'string', 'max:190'],
        'tax_id' => ['nullable', 'string', 'max:120'],
        'billing_email' => ['required', 'email', 'max:190'],
        'payout_method' => ['required', Rule::in(['bank_transfer', 'mobile_wallet', 'manual'])],
        'payout_reference' => ['nullable', 'string', 'max:190'],
        'bank_name' => ['nullable', 'string', 'max:190'],
        'bank_account_last4' => ['nullable', 'string', 'max:8'],
        'billing_address' => ['nullable', 'string', 'max:2000'],
        'currency' => ['nullable', 'string', 'max:8'],
        'invoice_prefix' => ['nullable', 'string', 'max:30'],
    ]);

    DB::table('vendor_billing_details')->updateOrInsert(
        [
            'vendor_user_id' => $vendorUserId,
        ],
        [
            'business_name' => trim((string) $validated['business_name']),
            'tax_id' => trim((string) ($validated['tax_id'] ?? '')),
            'billing_email' => strtolower(trim((string) $validated['billing_email'])),
            'payout_method' => (string) $validated['payout_method'],
            'payout_reference' => trim((string) ($validated['payout_reference'] ?? '')),
            'bank_name' => trim((string) ($validated['bank_name'] ?? '')),
            'bank_account_last4' => trim((string) ($validated['bank_account_last4'] ?? '')),
            'billing_address' => trim((string) ($validated['billing_address'] ?? '')),
            'currency' => strtoupper(trim((string) ($validated['currency'] ?? 'MVR'))),
            'invoice_prefix' => strtoupper(trim((string) ($validated['invoice_prefix'] ?? 'INV'))),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    return back()->with('portal_notice', 'Billing details updated.');
});
