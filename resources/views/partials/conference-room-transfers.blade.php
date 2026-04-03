{{-- Conference Room Transfer Options Section --}}
{{-- Display available transfer services when conference room is in a resort --}}

<style>
    .transfer-section {
        border: 1px solid #b6daea;
        border-radius: 14px;
        background: linear-gradient(135deg, #eaf6fb 0%, #e0f2f9 100%);
        padding: 16px;
        margin-bottom: 16px;
    }

    .transfer-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1a4b62;
        margin: 0 0 12px 0;
        font-family: "Space Grotesk", "Trebuchet MS", sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .transfer-section-title i {
        color: #0f6179;
        font-size: 1.2rem;
    }

    .transfer-context-note {
        font-size: 0.8rem;
        color: #1a4b62;
        line-height: 1.5;
        margin-bottom: 12px;
        padding: 10px;
        background: white;
        border-radius: 8px;
        border-left: 3px solid #0f6179;
    }

    .transfer-options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }

    .transfer-card {
        border: 2px solid #b6daea;
        border-radius: 10px;
        background: white;
        padding: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .transfer-card:hover {
        border-color: #0f6179;
        box-shadow: 0 4px 12px rgba(15, 97, 121, 0.12);
        transform: translateY(-2px);
    }

    .transfer-card input[type="checkbox"] {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .transfer-card label {
        display: block;
        cursor: pointer;
        padding-right: 30px;
    }

    .transfer-type-label {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--ink);
        margin: 0 0 6px 0;
    }

    .transfer-route {
        font-size: 0.8rem;
        color: var(--muted);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .transfer-route i {
        color: #0f6179;
        font-size: 0.9rem;
    }

    .transfer-description {
        font-size: 0.75rem;
        color: var(--muted);
        line-height: 1.4;
        margin-bottom: 10px;
    }

    .transfer-duration {
        font-size: 0.75rem;
        color: #4e6d83;
        background: #f0f8ff;
        padding: 4px 8px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 10px;
    }

    .transfer-pricing {
        border-top: 1px solid #d5e2ec;
        padding-top: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .transfer-price-per {
        font-size: 0.8rem;
        color: var(--muted);
    }

    .transfer-price-value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f6179;
    }

    .transfer-free-badge {
        display: inline-block;
        background: #d4f1de;
        color: #0b5a2f;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .transfer-card:has(input[type="checkbox"]:checked) {
        border-color: #0f6179;
        background: linear-gradient(135deg, #f0f8ff 0%, #e8f5ff 100%);
        box-shadow: 0 4px 12px rgba(15, 97, 121, 0.15);
    }

    .transfer-quantity-selector {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #d5e2ec;
    }

    .transfer-quantity-selector label {
        font-size: 0.8rem;
        color: var(--muted);
        font-weight: 600;
        margin: 0;
        cursor: pointer;
    }

    .transfer-quantity-selector select {
        padding: 5px 8px;
        border: 1px solid #c8d8e5;
        border-radius: 6px;
        font-size: 0.85rem;
        background: white;
        cursor: pointer;
    }

    .transfer-trip-option {
        font-size: 0.75rem;
        color: var(--muted);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .transfer-trip-option input[type="radio"] {
        cursor: pointer;
    }

    .transfer-trip-option label {
        cursor: pointer;
        margin: 0;
    }

    @media (max-width: 768px) {
        .transfer-options-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@if(isset($conferenceRoom) && $conferenceRoom->hasTransferOptions())
    <div class="transfer-section">
        <div class="transfer-section-title">
            <i class="fa-solid fa-shuttle-van"></i>
            Resort Transfer & Transport Services
        </div>

        <div class="transfer-context-note">
            <strong>{{ $conferenceRoom->resort_name }}</strong> is located on a private resort island. 
            Arrange your transportation with our convenient transfer options from {{ $conferenceRoom->airport_name }}
            or nearby islands. Prices shown are per person.
        </div>

        <div class="transfer-options-grid">
            @foreach($conferenceRoom->getAvailableTransfers() as $transfer)
                <div class="transfer-card">
                    <input 
                        type="checkbox" 
                        id="transfer_{{ $transfer->id }}" 
                        name="transfers[]" 
                        value="{{ $transfer->id }}"
                        data-transfer-price="{{ $transfer->price_per_person }}"
                        data-transfer-type="{{ $transfer->transfer_type }}"
                    >
                    <label for="transfer_{{ $transfer->id }}">
                        <div class="transfer-type-label">
                            {{ $transfer->getTypeLabel() }}
                            @if($transfer->price_per_person == 0)
                                <span class="transfer-free-badge">FREE</span>
                            @endif
                        </div>

                        <div class="transfer-route">
                            <i class="fa-solid fa-arrow-right-long"></i>
                            <span>{{ $transfer->origin_location }} → {{ $transfer->destination_location }}</span>
                        </div>

                        @if($transfer->description)
                            <div class="transfer-description">{{ $transfer->description }}</div>
                        @endif

                        @if($transfer->duration_minutes)
                            <div class="transfer-duration">
                                <i class="fa-solid fa-hourglass-end"></i>
                                {{ $transfer->duration_minutes }} minutes
                            </div>
                        @endif

                        <div class="transfer-quantity-selector">
                            <label for="transfer_pax_{{ $transfer->id }}">Passengers:</label>
                            <select id="transfer_pax_{{ $transfer->id }}" name="transfer_pax[{{ $transfer->id }}]">
                                @for($i = $transfer->group_size_min; $i <= min($transfer->group_size_max, 50); $i++)
                                    <option value="{{ $i }}" {{ $i === 1 ? 'selected' : '' }}>{{ $i }} person{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>

                        @if(in_array($transfer->transfer_type, ['airport_pickup', 'airport_dropoff']))
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #d5e2ec;">
                                <label class="transfer-trip-option">
                                    <input 
                                        type="radio" 
                                        name="transfer_trip_{{ $transfer->id }}" 
                                        value="1" 
                                        checked
                                    >
                                    <span>One-way</span>
                                </label>
                                <label class="transfer-trip-option">
                                    <input 
                                        type="radio" 
                                        name="transfer_trip_{{ $transfer->id }}" 
                                        value="2"
                                    >
                                    <span>Round-trip</span>
                                </label>
                            </div>
                        @endif

                        <div class="transfer-pricing">
                            <span class="transfer-price-per">
                                @if($transfer->price_per_person == 0)
                                    Complimentary
                                @else
                                    MVR {{ number_format($transfer->price_per_person, 0) }}/person
                                @endif
                            </span>
                        </div>
                    </label>
                </div>
            @endforeach
        </div>

        <div style="background: white; border-radius: 8px; padding: 12px; border: 1px solid #d5e2ec;">
            <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 8px;">
                <strong>Transfer Total:</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.8rem; color: var(--muted);">Selected transfers (calculated per passenger):</span>
                <span style="font-size: 1.1rem; font-weight: 700; color: #0f6179;">
                    MVR <span id="transfer-total-price">0.00</span>
                </span>
            </div>
        </div>
    </div>
@endif
