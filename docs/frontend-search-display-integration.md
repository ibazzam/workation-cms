# Frontend Search & Display Integration Guide

## Adding Atoll/Island Cascading to Search Form

The homepage search form (`welcome.blade.php`) can be enhanced to filter by atoll and island using the shared API.

### Option 1: Add Location Filters to Search Tabs

Add a new filter section within one of the category search panels:

```blade
<!-- Within search-dynamic-fields (e.g., accommodationFields) -->
<div class="field">
    <label for="searchAtoll">Atoll</label>
    <select id="searchAtoll" name="atoll" class="search-field-input">
        <option value="">-- All Atolls --</option>
    </select>
</div>
<div class="field">
    <label for="searchIsland">Island</label>
    <select id="searchIsland" name="island" class="search-field-input" disabled>
        <option value="">-- Select Atoll First --</option>
    </select>
</div>

<script>
// Load atolls on page load
async function initAtollSearch() {
    const atollSelect = document.getElementById('searchAtoll');
    const islandSelect = document.getElementById('searchIsland');
    
    const atolls = await fetch('/api/atoll-island/atolls').then(r => r.json());
    atolls.forEach(atoll => {
        const opt = document.createElement('option');
        opt.value = atoll.id;
        opt.text = atoll.name;
        atollSelect.appendChild(opt);
    });
    
    // Handle atoll change
    atollSelect.addEventListener('change', async function() {
        islandSelect.innerHTML = '<option value="">-- Select Island --</option>';
        if (!this.value) {
            islandSelect.disabled = true;
            return;
        }
        
        islandSelect.disabled = false;
        const islands = await fetch(`/api/atoll-island/atolls/${this.value}/islands`)
            .then(r => r.json());
        islands.forEach(island => {
            const opt = document.createElement('option');
            opt.value = island.id;
            opt.text = island.name;
            islandSelect.appendChild(opt);
        });
    });
}

document.addEventListener('DOMContentLoaded', initAtollSearch);
</script>
```

### Option 2: Sidebar Filter Widget

Create a collapsible sidebar filter for atoll/island location:

```blade
<aside class="search-filters">
    <div class="filter-group">
        <h3>Location</h3>
        <select id="filterAtoll" name="location_atoll" class="filter-select">
            <option value="">All Atolls</option>
        </select>
        <select id="filterIsland" name="location_island" class="filter-select" disabled>
            <option value="">All Islands</option>
        </select>
        <button class="filter-apply-btn">Apply Filters</button>
    </div>
</aside>
```

---

## Using Island Photos as Thumbnails

### 1. Homepage "Most Loved Destinations" Section

Replace static images with island data from the API:

```blade
<section class="most-loved-destinations">
    <h2>Most Loved Destinations</h2>
    <div class="destination-grid" id="destinationGrid">
        <!-- Loaded via JavaScript -->
    </div>
</section>

<script>
async function loadDestinations() {
    const destinations = await fetch('/api/atoll-island/islands?limit=12')
        .then(r => r.json());
    
    const grid = document.getElementById('destinationGrid');
    grid.innerHTML = destinations.map(island => `
        <a href="/islands/${island.slug}" class="destination-card">
            <div class="destination-image">
                <img 
                    src="{{ Storage::disk('public')->url('') }}${island.photo_path || 'placeholder.jpg'}"
                    alt="${island.name}"
                    onerror="this.src='data:image/svg+xml,...'"
                >
            </div>
            <div class="destination-info">
                <h3>${island.name}</h3>
                <p class="atoll-name">${island.atoll_name}</p>
            </div>
        </a>
    `).join('');
}

document.addEventListener('DOMContentLoaded', loadDestinations);
</script>
```

### 2. Category Cards with Island Thumbnails

When displaying category cards (accommodation, transport, etc.), show featured islands:

```blade
<section class="category-showcase">
    <h2>Accommodation Options</h2>
    <div class="category-cards" id="accommodationCards">
        <!-- Loaded via API -->
    </div>
</section>

<script>
async function loadCategoryCards() {
    // Fetch resort islands for display
    const islands = await fetch('/api/atoll-island/islands?type=resort&limit=6')
        .then(r => r.json());
    
    const cards = document.getElementById('accommodationCards');
    cards.innerHTML = islands.map(island => `
        <div class="category-card">
            <img src="{{ Storage::disk('public')->url('') }}${island.photo_path}" alt="${island.name}">
            <h3>${island.name}</h3>
            <p>${island.atoll_name}</p>
            <a href="/catalog/accommodation?island=${island.id}" class="btn">Browse</a>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', loadCategoryCards);
</script>
```

### 3. Catalog Listing Cards

When showing properties/listings in catalog results, include island/atoll information:

```blade
@forelse ($listings as $listing)
    <div class="listing-card">
        <div class="listing-image">
            @if ($listing->photo_path)
                <img src="{{ Storage::disk('public')->url($listing->photo_path) }}" alt="">
            @elseif ($listing->island && $listing->island->photo_path)
                <img src="{{ Storage::disk('public')->url($listing->island->photo_path) }}" alt="{{ $listing->island->name }}">
            @else
                <div class="image-placeholder">📍</div>
            @endif
        </div>
        <div class="listing-info">
            <h4>{{ $listing->name }}</h4>
            <p class="location">
                <span class="island">{{ $listing->island->name ?? 'Unknown Island' }}</span>
                <span class="atoll">{{ $listing->island->atoll->name ?? 'Unknown Atoll' }}</span>
            </p>
            <p class="price">{{ $listing->price }} / night</p>
        </div>
    </div>
@endforelse
```

---

## Image Fallback Strategy

### For Missing Island Photos
When `island.photo_path` is null or image fails to load, provide fallback:

```blade
<img 
    src="{{ $island->photo_path ? Storage::disk('public')->url($island->photo_path) : '/images/default-island.jpg' }}"
    alt="{{ $island->name }}"
    loading="lazy"
    onerror="this.src='/images/island-placeholder.svg'; this.dataset.fallback='true';"
>
```

### SVG Placeholder
Create a lightweight island placeholder:

```svg
<!-- public/images/island-placeholder.svg -->
<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="#e8f4f8"/>
    <circle cx="100" cy="100" r="50" fill="#43be66"/>
    <path d="M 60 120 Q 100 140 140 120" fill="#4a90ba"/>
    <text x="100" y="170" font-size="12" text-anchor="middle" fill="#666">Island</text>
</svg>
```

---

## Database Schema Requirements

Ensure these relationships exist:

### Islands Table
```
- id (primary)
- name
- slug
- atoll_id (foreign key → atolls.id)
- photo_path (nullable string)
- island_type (enum: inhabited | uninhabited | resort)
- is_inhabited (boolean)
```

### Atolls Table
```
- id (primary)
- name
- slug
- code (3-letter code)
- photo_path (nullable string)
```

### Listings/Properties Table (if storing location)
```
- id (primary)
- atoll_id (nullable foreign key → atolls.id)
- island_id (nullable foreign key → islands.id)
- ...other fields
```

---

## JavaScript Utility Function

Create a reusable helper for island/atoll UI operations:

```javascript
// public/js/atoll-island-utils.js

const AtollIslandUtil = {
    /**
     * Load atolls and populate a select element
     */
    async loadAtollsIntoSelect(selectElement, selectedAtollId = null) {
        const atolls = await fetch('/api/atoll-island/atolls')
            .then(r => r.json());
        
        selectElement.innerHTML = '<option value="">-- Select Atoll --</option>' +
            atolls.map(a => `<option value="${a.id}" ${selectedAtollId == a.id ? 'selected' : ''}>${a.name}</option>`)
                .join('');
    },

    /**
     * Load islands for an atoll
     */
    async loadIslandsByAtoll(atollId, selectedIslandId = null) {
        const islands = await fetch(`/api/atoll-island/atolls/${atollId}/islands`)
            .then(r => r.json());
        
        return islands;
    },

    /**
     * Create cascading select behavior
     */
    createCascade(atollSelectId, islandSelectId) {
        const atollSelect = document.getElementById(atollSelectId);
        const islandSelect = document.getElementById(islandSelectId);

        this.loadAtollsIntoSelect(atollSelect);

        atollSelect.addEventListener('change', async (e) => {
            if (!e.target.value) {
                islandSelect.disabled = true;
                islandSelect.innerHTML = '<option value="">-- Select Atoll First --</option>';
                return;
            }

            const islands = await this.loadIslandsByAtoll(e.target.value);
            islandSelect.innerHTML = '<option value="">-- Select Island --</option>' +
                islands.map(i => `<option value="${i.id}">${i.name}</option>`)
                    .join('');
            islandSelect.disabled = false;
        });
    },

    /**
     * Get island thumbnail URL with fallback
     */
    getIslandPhotoUrl(island) {
        if (!island || !island.photo_path) {
            return '/images/island-placeholder.svg';
        }
        return `/storage/${island.photo_path}`;
    }
};

// Usage:
// AtollIslandUtil.createCascade('atoll-select', 'island-select');
```

---

## Testing Integration

### Sample Query Strings
```
/catalog/accommodation?atoll=1&island=5
/catalog/accommodation?island=10
/islands?type=resort
/search?q=south&atoll=2
```

### Verify API Responses
```bash
# Test atoll loading
curl http://localhost/api/atoll-island/atolls

# Test island loading for specific atoll
curl http://localhost/api/atoll-island/atolls/1/islands

# Test featured islands
curl http://localhost/api/atoll-island/islands?limit=6
```

---

## Performance Notes

- API endpoints are lightweight and cacheable
- Use `?limit=` parameter to control island count in carousels
- Lazy-load images with `loading="lazy"` attribute
- Cache island list in sessionStorage for repeated access
- Consider CDN for frequently-accessed island photos
