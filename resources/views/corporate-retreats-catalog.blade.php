{{-- Corporate Retreat Packages Catalog --}}
@extends('layouts.app')

@section('content')
<div class="corporate-retreats-container">
    {{-- ─── Header Section with Hero ─────────────────────────────── --}}
    <div class="corporate-retreats-hero">
        <div class="corporate-retreats-hero-content">
            <h1 class="corporate-retreats-title">Corporate Retreat Packages</h1>
            <p class="corporate-retreats-subtitle">Curated team experiences in the Maldives</p>
            <p class="corporate-retreats-description">
                Transform your team with premium retreat packages designed for corporate groups. 
                From intimate getaways to large-scale summits, find your perfect island escape.
            </p>
        </div>
        <div class="corporate-retreats-hero-background"></div>
    </div>

    {{-- ─── Package Tiers Overview ──────────────────────────────── --}}
    <div class="corporate-retreats-tiers-section">
        <h2 class="corporate-retreats-section-title">Our Retreat Packages</h2>
        <div class="corporate-retreats-tiers-grid">
            <div class="corporate-retreat-tier">
                <div class="tier-badge">GETAWAY</div>
                <h3>Getaway</h3>
                <p class="tier-duration">1 Night / 2 Days</p>
                <p class="tier-group-size">5–20 Participants</p>
                <p class="tier-price">From <strong>₨2,500</strong> per person</p>
                <p class="tier-description">Perfect for quick team bonding and executive briefings</p>
            </div>
            <div class="corporate-retreat-tier tier-featured">
                <div class="tier-badge tier-badge-featured">RETREAT</div>
                <h3>Retreat</h3>
                <p class="tier-duration">2 Nights / 3 Days</p>
                <p class="tier-group-size">10–50 Participants</p>
                <p class="tier-price">From <strong>₨4,200</strong> per person</p>
                <p class="tier-description">Our most popular choice for team building & strategy sessions</p>
                <div class="tier-badge-highlight">Most Popular</div>
            </div>
            <div class="corporate-retreat-tier">
                <div class="tier-badge">SUMMIT</div>
                <h3>Summit</h3>
                <p class="tier-duration">3 Nights / 4 Days</p>
                <p class="tier-group-size">10–80 Participants</p>
                <p class="tier-price">From <strong>₨6,500</strong> per person</p>
                <p class="tier-description">Comprehensive programs for major corporate events</p>
            </div>
        </div>
    </div>

    {{-- ─── Available Packages ──────────────────────────────────── --}}
    <div class="corporate-retreats-packages-section">
        <div class="corporate-retreats-section-header">
            <h2 class="corporate-retreats-section-title">Available Packages</h2>
            <div class="corporate-retreats-filters">
                <div class="filter-group">
                    <label for="island-filter">Island</label>
                    <select id="island-filter" class="corporate-filter-select">
                        <option value="">All Islands</option>
                        @foreach (($islandOptions ?? []) as $island)
                            <option value="{{ $island['value'] ?? '' }}">{{ $island['label'] ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label for="group-size-filter">Group Size</label>
                    <select id="group-size-filter" class="corporate-filter-select">
                        <option value="">All Sizes</option>
                        <option value="small">5–20 people</option>
                        <option value="medium">21–50 people</option>
                        <option value="large">51–80 people</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search-filter">Search</label>
                    <input type="text" id="search-filter" class="corporate-filter-input" placeholder="Package name…">
                </div>
            </div>
        </div>

        <div class="corporate-retreats-cards-grid">
            @if (($catalogProperties ?? []) && count($catalogProperties) > 0)
                @foreach ($catalogProperties as $property)
                    @php
                        $propertyId = (int) ($property->id ?? $property->vendor_property_id ?? 0);
                        $packageName = trim((string) ($property->name ?? 'Retreat Package'));
                        $description = trim((string) ($property->short_description ?? $property->description ?? ''));
                        $island = $property->island ?? null;
                        $islandName = $island ? ($island->name ?? 'Unknown Island') : 'Unknown Island';
                        $basePrice = (float) ($property->price ?? 0);
                        $currency = (string) ($property->currency ?? 'USD');
                        
                        // Get thumbnail
                        $mediaCollection = ($catalogPropertyMediaByProperty ?? collect())
                            ->get($propertyId, collect());
                        $primaryMedia = $mediaCollection
                            ->first();
                        $thumbUrl = $primaryMedia 
                            ? portalManagedMediaUrlFromPath((string) ($primaryMedia->file_path ?? ''))
                            : '';

                        $detailUrl = "/category-booking/excursion/" . $propertyId;
                    @endphp
                    <div class="corporate-retreat-card">
                        <div class="corporate-retreat-card-image">
                            @if ($thumbUrl)
                                <img src="{{ $thumbUrl }}" alt="{{ $packageName }}" loading="lazy">
                            @else
                                <div class="corporate-retreat-card-image-placeholder">
                                    <i class="fa-solid fa-briefcase"></i>
                                </div>
                            @endif
                        </div>
                        
                        <div class="corporate-retreat-card-content">
                            <h3 class="corporate-retreat-card-title">{{ $packageName }}</h3>
                            <p class="corporate-retreat-card-island">
                                <i class="fa-solid fa-map-pin"></i> {{ $islandName }}
                            </p>
                            <p class="corporate-retreat-card-description">{{ Str::limit($description, 120) }}</p>
                            
                            <div class="corporate-retreat-card-details">
                                <div class="detail-item">
                                    <span class="detail-label">Starting at</span>
                                    <span class="detail-value">
                                        {{ $currency === 'USD' ? '$' : '₨' }} {{ number_format($basePrice, 0) }}<span class="detail-unit">/person</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="corporate-retreat-card-footer">
                            <a href="{{ $detailUrl }}" class="corporate-retreat-btn corporate-retreat-btn-primary">
                                View Details <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <button class="corporate-retreat-btn corporate-retreat-btn-secondary">
                                Request Quote
                            </button>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="corporate-retreats-empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <h3>No packages found</h3>
                    <p>Check back soon for available corporate retreat packages</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Included Features ──────────────────────────────────── --}}
    <div class="corporate-retreats-features-section">
        <h2 class="corporate-retreats-section-title">What's Included</h2>
        <div class="corporate-retreats-features-grid">
            <div class="corporate-feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-bed"></i>
                </div>
                <h4>Accommodation</h4>
                <p>Premium beachfront resorts and villas</p>
            </div>
            <div class="corporate-feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h4>Meals & Beverages</h4>
                <p>Full board with local & international cuisine</p>
            </div>
            <div class="corporate-feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-dumbbell"></i>
                </div>
                <h4>Team Activities</h4>
                <p>Diving, snorkeling, water sports & more</p>
            </div>
            <div class="corporate-feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-video"></i>
                </div>
                <h4>Meeting Spaces</h4>
                <p>Conference rooms with AV equipment</p>
            </div>
            <div class="corporate-feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-person-hiking"></i>
                </div>
                <h4>Island Transfers</h4>
                <p>Speedboat & ferry arrangements included</p>
            </div>
            <div class="corporate-feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h4>Dedicated Support</h4>
                <p>24/7 concierge for your group</p>
            </div>
        </div>
    </div>
</div>

<style>
    .corporate-retreats-container {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
    }

    .corporate-retreats-hero {
        position: relative;
        background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8c 100%);
        color: white;
        padding: 80px 40px;
        text-align: center;
        overflow: hidden;
    }

    .corporate-retreats-hero-background {
        position: absolute;
        top: 0;
        right: 0;
        width: 40%;
        height: 100%;
        opacity: 0.1;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="white" d="M100,10 Q150,50 150,100 Q150,150 100,180 Q50,150 50,100 Q50,50 100,10" opacity="0.3"/><circle cx="100" cy="100" r="60" fill="none" stroke="white" stroke-width="2" opacity="0.2"/></svg>') no-repeat center;
    }

    .corporate-retreats-hero-content {
        position: relative;
        z-index: 1;
        max-width: 800px;
        margin: 0 auto;
    }

    .corporate-retreats-title {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0 0 16px 0;
        letter-spacing: -0.5px;
    }

    .corporate-retreats-subtitle {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0 0 24px 0;
        opacity: 0.95;
    }

    .corporate-retreats-description {
        font-size: 1rem;
        line-height: 1.6;
        margin: 0;
        opacity: 0.9;
    }

    .corporate-retreats-section-title {
        font-size: 2rem;
        font-weight: 700;
        color: #1e3a5f;
        margin: 0 0 32px 0;
        text-align: center;
    }

    .corporate-retreats-tiers-section {
        max-width: 1200px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .corporate-retreats-tiers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-top: 48px;
    }

    .corporate-retreat-tier {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 32px 24px;
        text-align: center;
        position: relative;
        transition: all 0.3s ease;
    }

    .corporate-retreat-tier:hover {
        border-color: #1e3a5f;
        box-shadow: 0 12px 24px rgba(30, 58, 95, 0.12);
        transform: translateY(-4px);
    }

    .corporate-retreat-tier.tier-featured {
        border: 2px solid #1e3a5f;
        background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
        transform: scale(1.05);
        box-shadow: 0 12px 32px rgba(30, 58, 95, 0.15);
    }

    .tier-badge {
        display: inline-block;
        background: #e2e8f0;
        color: #1e3a5f;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 1px;
        padding: 6px 14px;
        border-radius: 20px;
        margin-bottom: 16px;
    }

    .tier-badge-featured {
        background: #1e3a5f;
        color: white;
    }

    .tier-badge-highlight {
        display: inline-block;
        background: #fbbf24;
        color: #78350f;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        margin-top: 12px;
    }

    .corporate-retreat-tier h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e3a5f;
        margin: 12px 0;
    }

    .tier-duration {
        font-size: 0.95rem;
        font-weight: 600;
        color: #475569;
        margin: 8px 0;
    }

    .tier-group-size {
        font-size: 0.9rem;
        color: #64748b;
        margin: 4px 0 12px 0;
    }

    .tier-price {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e3a5f;
        margin: 12px 0 8px 0;
    }

    .tier-description {
        font-size: 0.9rem;
        color: #64748b;
        line-height: 1.5;
        margin: 12px 0 0 0;
    }

    .corporate-retreats-packages-section {
        max-width: 1400px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .corporate-retreats-section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 40px;
        gap: 32px;
    }

    .corporate-retreats-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        min-width: 500px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .corporate-filter-select,
    .corporate-filter-input {
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.9rem;
        font-family: inherit;
        background: white;
        color: #1e3a5f;
    }

    .corporate-filter-select:focus,
    .corporate-filter-input:focus {
        outline: none;
        border-color: #1e3a5f;
        box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.1);
    }

    .corporate-retreats-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 28px;
    }

    .corporate-retreat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(30, 58, 95, 0.08);
    }

    .corporate-retreat-card:hover {
        border-color: #1e3a5f;
        box-shadow: 0 12px 32px rgba(30, 58, 95, 0.15);
        transform: translateY(-6px);
    }

    .corporate-retreat-card-image {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, #dbeafe 0%, #cffafe 100%);
        overflow: hidden;
        position: relative;
    }

    .corporate-retreat-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .corporate-retreat-card-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #dbeafe 0%, #cffafe 100%);
        color: #0284c7;
        font-size: 3rem;
    }

    .corporate-retreat-card-content {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .corporate-retreat-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e3a5f;
        margin: 0 0 8px 0;
        line-height: 1.4;
    }

    .corporate-retreat-card-island {
        font-size: 0.85rem;
        color: #64748b;
        margin: 0 0 12px 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .corporate-retreat-card-description {
        font-size: 0.9rem;
        color: #64748b;
        line-height: 1.5;
        margin: 0 0 16px 0;
        flex: 1;
    }

    .corporate-retreat-card-details {
        margin: 12px 0 0 0;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .detail-label {
        font-size: 0.8rem;
        color: #94a3b8;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .detail-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e3a5f;
    }

    .detail-unit {
        font-size: 0.8rem;
        font-weight: 500;
        color: #64748b;
        margin-left: 4px;
    }

    .corporate-retreat-card-footer {
        padding: 16px 20px 20px;
        display: flex;
        gap: 12px;
        flex-direction: column;
    }

    .corporate-retreat-btn {
        padding: 11px 16px;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-family: inherit;
    }

    .corporate-retreat-btn-primary {
        background: #1e3a5f;
        color: white;
    }

    .corporate-retreat-btn-primary:hover {
        background: #0f2438;
        box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
    }

    .corporate-retreat-btn-secondary {
        background: #f1f5f9;
        color: #1e3a5f;
        border: 1px solid #cbd5e1;
    }

    .corporate-retreat-btn-secondary:hover {
        background: #e2e8f0;
        border-color: #1e3a5f;
    }

    .corporate-retreats-empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 80px 40px;
        color: #64748b;
    }

    .corporate-retreats-empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .corporate-retreats-empty-state h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e3a5f;
        margin: 0 0 8px 0;
    }

    .corporate-retreats-empty-state p {
        margin: 0;
        font-size: 0.95rem;
    }

    .corporate-retreats-features-section {
        background: white;
        margin-top: 80px;
        padding: 80px 40px;
    }

    .corporate-retreats-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 32px;
        max-width: 1400px;
        margin: 48px auto 0;
    }

    .corporate-feature-card {
        text-align: center;
        padding: 32px 24px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
        background: #f8fafc;
    }

    .corporate-feature-card:hover {
        border-color: #1e3a5f;
        background: #eff6ff;
        transform: translateY(-4px);
    }

    .feature-icon {
        font-size: 2.5rem;
        color: #1e3a5f;
        margin-bottom: 16px;
    }

    .corporate-feature-card h4 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e3a5f;
        margin: 0 0 8px 0;
    }

    .corporate-feature-card p {
        font-size: 0.9rem;
        color: #64748b;
        margin: 0;
        line-height: 1.5;
    }

    @media (max-width: 768px) {
        .corporate-retreats-hero {
            padding: 60px 20px;
        }

        .corporate-retreats-title {
            font-size: 2rem;
        }

        .corporate-retreats-subtitle {
            font-size: 1.1rem;
        }

        .corporate-retreats-section-header {
            flex-direction: column;
        }

        .corporate-retreats-filters {
            min-width: auto;
            width: 100%;
            grid-template-columns: 1fr;
        }

        .corporate-retreats-tiers-grid {
            grid-template-columns: 1fr;
        }

        .corporate-retreat-tier.tier-featured {
            transform: scale(1);
        }

        .corporate-retreats-cards-grid {
            grid-template-columns: 1fr;
        }

        .corporate-retreats-features-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
