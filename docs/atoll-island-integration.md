# Atoll & Island Integration Guide

## Overview
All atolls and islands are now integrated across the application via:
- **API Endpoints**: `/api/atoll-island/*` for dynamic data loading
- **Blade Component**: `components.atoll-island-select` for cascading dropdowns
- **Data Backend**: Island classification locked with island_type (20 atolls, 1079 islands)

## Island Classification
- **Inhabited (Local)**: 190 islands — public-facing, inhabited communities
- **Resort**: 1 island — tourism-focused, resort-only operations
- **Uninhabited**: 888 islands — including agriculture/farming/aquaculture leases

## Available API Endpoints

### 1. Get All Atolls
```
GET /api/atoll-island/atolls
Response: [
  { id: 1, name: "Addu Atoll", slug: "addu", code: "ADU" },
  ...
]
```

### 2. Get Islands by Atoll
```
GET /api/atoll-island/atolls/{atoll_id}/islands?type={inhabited|uninhabited|resort}
Response: [
  { id: 123, name: "Gan", slug: "gan", island_type: "inhabited", is_inhabited: true },
  ...
]
```

### 3. Get Atoll Statistics
```
GET /api/atoll-island/atolls/{atoll_id}/stats
Response: {
  id: 1,
  name: "Addu Atoll",
  total_islands: 35,
  inhabited: 5,
  uninhabited: 25,
  resort: 5
}
```

### 4. Get Island with Media
```
GET /api/atoll-island/islands/{island_id}
Response: {
  id: 123,
  name: "Gan",
  slug: "gan",
  atoll_name: "Addu Atoll",
  photo_path: "path/to/photo.jpg",
  island_type: "inhabited"
}
```

### 5. Get Featured Islands (for thumbnails)
```
GET /api/atoll-island/islands?type={type}&limit=12
Response: [
  { id: 123, name: "Gan", slug: "gan", atoll_name: "Addu Atoll", photo_path: "...", island_type: "inhabited" },
  ...
]
```

## Using the Cascading Select Component

### Basic Usage in Vendor Forms
```blade
@include('components.atoll-island-select', [
    'selectedAtoll' => old('atoll_id', $profile['atoll_id'] ?? null),
    'selectedIsland' => old('island_id', $profile['island_id'] ?? null),
])
```

### Custom Field Names and Labels
```blade
@include('components.atoll-island-select', [
    'fieldNameAtoll' => 'business_atoll',
    'fieldNameIsland' => 'business_island',
    'labelAtoll' => 'Business Location Atoll',
    'labelIsland' => 'Business Location Island',
    'selectedAtoll' => $profile['atoll_id'] ?? null,
    'selectedIsland' => $profile['island_id'] ?? null,
])
```

### Optional Fields
```blade
@include('components.atoll-island-select', [
    'requiredAtoll' => false,
    'requiredIsland' => false,
    'selectedAtoll' => $profile['atoll_id'] ?? null,
    'selectedIsland' => $profile['island_id'] ?? null,
])
```

### Custom CSS Class
```blade
@include('components.atoll-island-select', [
    'cssClass' => 'my-custom-select-class',
    'selectedAtoll' => $profile['atoll_id'] ?? null,
    'selectedIsland' => $profile['island_id'] ?? null,
])
```

## Integration Checklist

### Vendor Portal Forms
- [ ] Profile form: Add atoll/island for business location
- [ ] Listing creation form: Add atoll/island per category
- [ ] Vendor address/contact form: Add atoll/island fields

### Frontend Customer Forms
- [ ] Search form: Cascade search by atoll → island
- [ ] Booking form: Show island dropdown for atoll filter
- [ ] Accommodation booking: Allow island selection

### Frontend Display
- [ ] Homepage "Most Loved Destinations": Use island photos from `island.photo_path`
- [ ] Category cards: Show atoll/island thumbnails
- [ ] Search results: Display atoll/island info per listing
- [ ] Detail pages: Show host/property atoll/island location

### Database Schema
Ensure these columns exist on relevant tables:
- `vendors` (or profile table): `atoll_id`, `island_id`
- `listings` (or properties): `atoll_id`, `island_id`
- `reservations`: May include atoll_id/island_id for location context

## JavaScript Integration for Custom Forms

If not using the component, integrate manually:

```html
<select id="atoll" name="atoll_id" required>
  <option value="">-- Select atoll --</option>
</select>

<select id="island" name="island_id" required disabled>
  <option value="">-- First select atoll --</option>
</select>

<script>
const atollSelect = document.getElementById('atoll');
const islandSelect = document.getElementById('island');

atollSelect.addEventListener('change', async function() {
  if (!this.value) {
    islandSelect.disabled = true;
    islandSelect.innerHTML = '<option>-- Select atoll first --</option>';
    return;
  }

  const response = await fetch(`/api/atoll-island/atolls/${this.value}/islands`);
  const islands = await response.json();
  
  islandSelect.innerHTML = islands
    .map(i => `<option value="${i.id}">${i.name}</option>`)
    .join('');
  islandSelect.disabled = false;
});
</script>
```

## Image/Thumbnail Usage

### In Blade Views
```blade
{{-- Display island photo from database --}}
@if ($island->photo_path)
    <img src="{{ Storage::disk('public')->url($island->photo_path) }}" alt="{{ $island->name }}">
@else
    <div class="placeholder">🏝️ {{ $island->name }}</div>
@endif
```

### For Carousel/Grid Components
Fetch featured islands via API:
```javascript
fetch('/api/atoll-island/islands?limit=12&type=resort')
  .then(r => r.json())
  .then(islands => {
    islands.forEach(island => {
      // Render thumbnail: <img src="storage/path/to/island.photo_path">
    });
  });
```

## Notes
- All routes are available in production (not testing-only)
- Island data is cached efficiently via Eloquent queries
- Component auto-disables island select until atoll is chosen
- URLs can be fully-qualified for use in JavaScript/AJAX
