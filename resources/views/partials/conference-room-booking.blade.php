{{-- Conference Room Booking Form with Packages & Facilities --}}

{{-- Include transfer options section if available --}}
@if(isset($conferenceRoom) && $conferenceRoom->hasTransferOptions())
    @include('partials.conference-room-transfers')
@endif

<style>
    .booking-section {
        border: 1px solid #cbe0ea;
        border-radius: 14px;
        background: #fbfdff;
        padding: 16px;
        margin-bottom: 16px;
    }

    .booking-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--ink);
        margin: 0 0 12px 0;
        font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .package-card {
        border: 2px solid #e0eef5;
        border-radius: 12px;
        background: linear-gradient(135deg, #f8fcff 0%, #f0f8ff 100%);
        padding: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .package-card:hover {
        border-color: #0f6179;
        box-shadow: 0 4px 12px rgba(15, 97, 121, 0.1);
    }

    .package-card input[type="radio"] {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
        width: 18px;
        height: 18px;
    }

    .package-card label {
        display: block;
        cursor: pointer;
        padding-right: 30px;
    }

    .package-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--ink);
        margin: 0 0 6px 0;
    }

    .package-description {
        font-size: 0.8rem;
        color: var(--muted);
        line-height: 1.4;
        margin: 0 0 10px 0;
    }

    .package-details {
        font-size: 0.75rem;
        background: white;
        border-radius: 8px;
        padding: 8px;
        margin-bottom: 10px;
        color: #446080;
        line-height: 1.4;
    }

    .package-details strong {
        display: block;
        color: #0f6179;
        margin-bottom: 4px;
    }

    .package-price {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 10px;
        border-top: 1px solid #d5e2ec;
    }

    .price-label {
        font-size: 0.8rem;
        color: var(--muted);
        font-weight: 600;
    }

    .price-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f6179;
    }

    .package-card input[type="radio"]:checked ~ label {
        font-weight: 700;
    }

    .package-card:has(input[type="radio"]:checked) {
        border-color: #0f6179;
        background: linear-gradient(135deg, #f0f8ff 0%, #e8f5ff 100%);
        box-shadow: 0 4px 12px rgba(15, 97, 121, 0.15);
    }

    .facilities-section {
        margin-top: 16px;
    }

    .facility-category-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #4e6d83;
        margin-bottom: 10px;
        margin-top: 14px;
        display: block;
        font-family: "Space Grotesk", "Trebucheit MS", sans-serif;
    }

    .facility-category-label:first-child {
        margin-top: 0;
    }

    .facilities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .facility-item {
        border: 1px solid #d5e2ec;
        border-radius: 10px;
        background: white;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .facility-item input[type="checkbox"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }

    .facility-header {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .facility-content {
        flex: 1;
    }

    .facility-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--ink);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .facility-free-badge {
        display: inline-block;
        background: #d4f1de;
        color: #0b5a2f;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .facility-description {
        font-size: 0.8rem;
        color: var(--muted);
        line-height: 1.3;
    }

    .facility-pricing {
        font-size: 0.8rem;
        color: #0f6179;
        font-weight: 600;
    }

    .facility-item input[type="checkbox"]:checked + .facility-header {
        font-weight: 700;
    }

    .facility-quantity {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
    }

    .facility-quantity label {
        font-size: 0.8rem;
        color: var(--muted);
    }

    .facility-quantity input[type="number"] {
        width: 60px;
        padding: 4px 8px;
        border: 1px solid #c8d8e5;
        border-radius: 6px;
        font-size: 0.85rem;
    }

    .booking-summary {
        border: 1px solid #b6daea;
        border-radius: 12px;
        background: #eaf6fb;
        padding: 14px;
        margin-top: 16px;
    }

    .summary-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1a4b62;
        margin-bottom: 10px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        color: #1a4b62;
        margin-bottom: 6px;
        line-height: 1.4;
    }

    .summary-divider {
        border-top: 1px solid #b6daea;
        margin: 10px 0;
    }

    .summary-total {
        display: flex;
        justify-content: space-between;
        font-size: 1rem;
        font-weight: 700;
        color: #0f6179;
    }

    @media (max-width: 768px) {
        .packages-grid {
            grid-template-columns: 1fr;
        }

        .facilities-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="booking-section">
    <h3 class="booking-section-title">📦 Pre-Configured Packages</h3>
    <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 12px;">
        Choose a ready-made package or build a custom selection below. Packages offer the best value for multi-day conferences.
    </p>

    <div class="packages-grid">
        <div class="package-card">
            <input type="radio" id="pkg_none" name="conference_package" value="none" checked>
            <label for="pkg_none">
                <div class="package-name">✓ À La Carte</div>
                <div class="package-description">Select individual facilities below to customize your booking.</div>
                <div class="package-price">
                    <span class="price-label">Base Room Rate</span>
                    <span class="price-value">Custom</span>
                </div>
            </label>
        </div>

        @if(isset($conferenceRoom) && $conferenceRoom->packages()->where('is_active', true)->exists())
            @forelse($conferenceRoom->packages()->where('is_active', true)->get() as $package)
                <div class="package-card">
                    <input type="radio" id="pkg_{{ $package->id }}" name="conference_package" value="{{ $package->id }}">
                    <label for="pkg_{{ $package->id }}">
                        <div class="package-name">
                            @if($package->package_type === 'premium')
                                ⭐
                            @elseif($package->package_type === 'standard')
                                ✨
                            @else
                                ✓
                            @endif
                            {{ $package->name }}
                        </div>
                        <div class="package-description">{{ $package->description }}</div>
                        <div class="package-details">
                            <strong>Includes:</strong>
                            @foreach($package->facilities as $facility)
                                • {{ $facility->name }}<br>
                            @endforeach
                        </div>
                        @if($package->discount_percentage > 0)
                            <div style="font-size: 0.8rem; color: #d97706; font-weight: 700; margin-bottom: 8px;">
                                💰 {{ $package->discount_percentage }}% Discount
                            </div>
                        @endif
                        <div class="package-price">
                            <span class="price-label">{{ $package->duration_days }}-Day Package</span>
                            <span class="price-value">MVR {{ number_format($package->calculateTotalPrice(pax: 1), 2) }}</span>
                        </div>
                    </label>
                </div>
            @empty
            @endforelse
        @endif
    </div>
</div>

<div class="booking-section">
    <h3 class="booking-section-title">🔧 Additional Facilities & Services</h3>
    <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 12px;">
        Customize your event with any of these add-ons. Prices are calculated based on duration and attendee count.
    </p>

    <div class="facilities-section">
        @if(isset($conferenceRoom))
            @php
                $categories = [
                    'equipment' => '⚙️ Equipment',
                    'refreshment' => '☕ Refreshments',
                    'catering' => '🍽️ Catering',
                    'service' => '👔 Services',
                ];
            @endphp

            @foreach($categories as $catkey => $catLabel)
                @php
                    $facilitiesInCategory = $conferenceRoom->facilities()
                        ->where('category', $catkey)
                        ->where('is_available', true)
                        ->get();
                @endphp

                @if($facilitiesInCategory->count() > 0)
                    <label class="facility-category-label">{{ $catLabel }}</label>
                    <div class="facilities-grid">
                        @foreach($facilitiesInCategory as $facility)
                            <div class="facility-item">
                                <div class="facility-header">
                                    <input 
                                        type="checkbox" 
                                        id="fac_{{ $facility->id }}" 
                                        name="facilities[]" 
                                        value="{{ $facility->id }}"
                                        data-facility-price="{{ $facility->price }}"
                                        data-facility-type="{{ $facility->pricing_type }}"
                                    >
                                    <div class="facility-content">
                                        <div class="facility-name">
                                            {{ $facility->name }}
                                            @if($facility->is_free)
                                                <span class="facility-free-badge">FREE</span>
                                            @endif
                                        </div>
                                        @if($facility->description)
                                            <div class="facility-description">{{ $facility->description }}</div>
                                        @endif
                                        @if(!$facility->is_free)
                                            <div class="facility-pricing">
                                                @switch($facility->pricing_type)
                                                    @case('hourly')
                                                        MVR {{ number_format($facility->price, 0) }} / hour
                                                        @break
                                                    @case('per_unit')
                                                        MVR {{ number_format($facility->price, 0) }} / unit
                                                        @break
                                                    @case('per_pax')
                                                        MVR {{ number_format($facility->price, 0) }} / person
                                                        @break
                                                    @case('per_meal')
                                                        MVR {{ number_format($facility->price, 0) }} / person / day
                                                        @break
                                                    @case('flat')
                                                        MVR {{ number_format($facility->price, 0) }} flat
                                                        @break
                                                @endswitch
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($facility->pricing_type === 'per_unit' || $facility->pricing_type === 'hourly')
                                    <div class="facility-quantity">
                                        <label for="qty_{{ $facility->id }}">
                                            @if($facility->pricing_type === 'per_unit')
                                                Quantity:
                                            @else
                                                Hours:
                                            @endif
                                        </label>
                                        <input 
                                            type="number" 
                                            id="qty_{{ $facility->id }}" 
                                            name="facility_qty[{{ $facility->id }}]" 
                                            value="0" 
                                            min="0" 
                                            @if($facility->pricing_type === 'per_unit')
                                                max="{{ $facility->quantity_available ?? 100 }}"
                                            @endif
                                        >
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>

<div class="booking-summary">
    <div class="summary-title">Booking Summary & Pricing</div>
    <div class="summary-item">
        <span>Conference Room (1 day):</span>
        <span>MVR <span id="base-price">0.00</span></span>
    </div>
    <div class="summary-item">
        <span>Selected Facilities & Add-ons:</span>
        <span>MVR <span id="facilities-price">0.00</span></span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-total">
        <span>Total Estimated Cost:</span>
        <span>MVR <span id="total-price">0.00</span></span>
    </div>
</div>
