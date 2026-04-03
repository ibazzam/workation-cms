@php
    $sidebarVendorCategoryMap = $vendorCategoryMap ?? [];
    $sidebarSelectedVendorCategories = collect($selectedVendorCategories ?? []);
    $sidebarManagementLinks = $sidebarSelectedVendorCategories->flatMap(function ($categoryKey) use ($sidebarVendorCategoryMap) {
        $normalized = strtolower(trim((string) $categoryKey));
        if ($normalized === 'transport') {
            return [
                ['url' => '/vendor/listings/manage/marine-transport', 'label' => 'Manage Marine Transport'],
                ['url' => '/vendor/listings/manage/land-transport', 'label' => 'Manage Land Transport'],
            ];
        }

        $label = $sidebarVendorCategoryMap[$normalized] ?? ucwords(str_replace('_', ' ', $normalized));

        return [[
            'url' => '/vendor/listings/manage/' . str_replace('_', '-', $normalized),
            'label' => 'Manage ' . $label,
        ]];
    })->values();

    $sidebarCreateLinks = $sidebarSelectedVendorCategories->flatMap(function ($categoryKey) use ($sidebarVendorCategoryMap) {
        $normalized = strtolower(trim((string) $categoryKey));
        if ($normalized === 'transport') {
            return [
                ['url' => '/vendor/listings/create/marine-transport', 'label' => 'Add Marine Transport'],
                ['url' => '/vendor/listings/create/land-transport', 'label' => 'Add Land Transport'],
            ];
        }

        $label = $sidebarVendorCategoryMap[$normalized] ?? ucwords(str_replace('_', ' ', $normalized));

        return [[
            'url' => '/vendor/listings/create/' . str_replace('_', '-', $normalized),
            'label' => 'Add ' . $label,
        ]];
    })->values();
@endphp

<nav class="portal-nav" aria-label="Vendor navigation">
    <a href="/vendor/overview" data-panel-key="overview">Dashboard Summary</a>
    <a href="/vendor#profile" data-panel-key="profile">Profile / Update</a>
    <a href="/vendor/listings/create" data-panel-key="listings">Add Listings</a>
    @foreach ($sidebarCreateLinks as $listingLink)
        <a href="{{ $listingLink['url'] }}" data-panel-key="listings">{{ $listingLink['label'] }}</a>
    @endforeach
    <a href="/vendor/listings/manage" data-panel-key="listings">Manage Listings</a>
    @foreach ($sidebarManagementLinks as $listingLink)
        <a href="{{ $listingLink['url'] }}" data-panel-key="listings">{{ $listingLink['label'] }}</a>
    @endforeach
    <a href="/vendor/operations" data-panel-key="reservations">Reservations / Bookings</a>
    <a href="/vendor/billing" data-panel-key="billing">Billing / Daily Collection</a>
    <a href="/vendor#api" data-panel-key="api">API Tools</a>
</nav>
