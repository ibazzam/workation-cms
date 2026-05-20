<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? ucwords(str_replace('_', ' ', $category)) }} | Partners Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @include('vendor-portal.partials.portal-styles')
</head>
<body>
    @php
        $backCategoryRoute = ($category ?? '') === 'corporate_retreat' ? 'excursion' : (string) ($category ?? 'excursion');
        $backCategoryLabel = ($category ?? '') === 'corporate_retreat'
            ? 'Excursions'
            : (string) ($categoryLabel ?? ucwords(str_replace('_', ' ', (string) ($category ?? 'listing'))));
    @endphp
    <main class="page page-listing-form">
        <section class="hero">
            <div class="hero-top">
                <div class="hero-head">
                    <span class="eyebrow">Vendor Workspace</span>
                    <h1>{{ $pageTitle ?? (($formType === 'edit' ? 'Edit ' : 'New ') . ($categoryLabel ?? ucwords(str_replace('_', ' ', $category)))) }}</h1>
                    <p>{{ $pageSubtitle ?? 'Fill in the required fields and save your listing. Fields shown are specific to this category.' }}</p>
                    <div class="hero-links">
                        <a class="hero-link" href="/vendor/listings/{{ $backCategoryRoute }}">← Back to {{ $backCategoryLabel }} Listings</a>
                        <a class="hero-link" href="/vendor">Portal Home</a>
                    </div>
                </div>
                <div class="hero-actions">
                    <div class="auth-bar">
                        <span class="auth-user">Signed in as {{ $portalUser }}</span>
                        <form method="POST" action="/portal/vendor/logout">
                            @csrf
                            <button class="logout" type="submit">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <div class="portal-shell">
            @include('vendor-portal.partials.sidebar')

            <div class="portal-content">
                @if (session('portal_notice'))
                    <div class="notice" role="status" aria-live="polite">{{ session('portal_notice') }}</div>
                @endif

                @if ($errors->any())
                    <div class="error" role="alert">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:4px 0 0; padding-left:16px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="ops-section">
                    <div class="ops-category-card">
                        <div class="ops-header">
                            <h3 class="ops-title">
                                {{ $pageTitle ?? (($formType === 'edit' ? 'Edit: ' . ($property->name ?? '') : 'New ' . ($categoryLabel ?? ucwords(str_replace('_', ' ', $category))) . ' Listing')) }}
                            </h3>
                            <span class="ops-chip">{{ $categoryLabel ?? ucwords(str_replace('_', ' ', $category)) }}</span>
                        </div>
                        <div class="listing-form-quality-strip" aria-label="Listing form quality guide">
                            <p class="small" style="margin:0 0 6px;"><strong>Simple 4-step listing process</strong></p>
                            <div class="listing-form-quality-grid">
                                <article>
                                    <p class="small" style="margin:0;font-weight:700;">1. Listing Basics</p>
                                    <p class="small" style="margin:4px 0 0;">Use a clear listing name and short description.</p>
                                </article>
                                <article>
                                    <p class="small" style="margin:0;font-weight:700;">2. Address &amp; Location</p>
                                    <p class="small" style="margin:4px 0 0;">Select country, atoll/state, island/city in order.</p>
                                </article>
                                <article>
                                    <p class="small" style="margin:0;font-weight:700;">3. Pricing &amp; Capacity</p>
                                    <p class="small" style="margin:4px 0 0;">Set capacity and cost fields clearly for operators.</p>
                                </article>
                                <article>
                                    <p class="small" style="margin:0;font-weight:700;">4. Media Quality</p>
                                    <p class="small" style="margin:4px 0 0;">Upload bright, clear photos after saving listing.</p>
                                </article>
                            </div>
                        </div>
                        @include('vendor-portal.partials.forms.' . $formType . '.' . $category)
                    </div>
                </section>

                @if ($formType === 'edit' && isset($property))
                <section class="ops-section" style="margin-top:12px;">
                    <div class="ops-category-card">
                        <div class="ops-header">
                            <h3 class="ops-title">Listing Photos</h3>
                            <span class="ops-chip">Media</span>
                        </div>
                        <p style="margin:0 0 10px; color:#3a5568; font-size:0.88rem;">Upload and manage photos for this listing from the Media Manager in your Listings Console.</p>
                        <a class="btn btn-secondary" href="/vendor/listings/{{ $category }}#media-{{ $property->id ?? '' }}">Open Media Manager</a>
                    </div>
                </section>
                @endif
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    (function () {
        // ── Sidebar nav group accordion ──────────────────────────────────────
        Array.from(document.querySelectorAll('[data-vendor-nav-toggle]')).forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                var groupKey = toggle.getAttribute('data-vendor-nav-toggle') || '';
                if (!groupKey) return;
                var body = document.querySelector('[data-vendor-nav-group="' + groupKey + '"]');
                if (!body) return;
                var isOpen = body.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        // ── Location tree (Maldives atoll/island cascade) ────────────────────
        var COUNTRY_OPTIONS = [
            'Maldives', 'Sri Lanka', 'India', 'United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman',
            'Singapore', 'Malaysia', 'Thailand', 'Indonesia', 'Philippines', 'Vietnam', 'Japan', 'South Korea', 'China',
            'Australia', 'New Zealand', 'United Kingdom', 'Ireland', 'France', 'Germany', 'Italy', 'Spain', 'Netherlands',
            'Switzerland', 'Austria', 'Sweden', 'Norway', 'Denmark', 'Finland', 'Portugal', 'Greece', 'Turkey', 'Egypt',
            'South Africa', 'Kenya', 'Tanzania', 'Mauritius', 'Seychelles', 'Canada', 'United States', 'Mexico', 'Brazil',
            'Argentina', 'Chile', 'Other'
        ];

        var FALLBACK_LOCATION_TREE = {
            "Maldives": {
                "Kaafu Atoll": ["Malé", "Hulhumalé", "Maafushi", "Guraidhoo", "Dhiffushi", "Thulusdhoo"],
                "Alif Alif Atoll": ["Rasdhoo", "Ukulhas", "Mathiveri", "Feridhoo"],
                "Alif Dhaal Atoll": ["Mahibadhoo", "Dhigurah", "Dhangethi", "Maamigili"],
                "Baa Atoll": ["Eydhafushi", "Dharavandhoo", "Maalhos", "Kendhoo"],
                "Lhaviyani Atoll": ["Naifaru", "Hinnavaru", "Felivaru"],
                "Noonu Atoll": ["Manadhoo", "Velidhoo", "Holhudhoo"],
                "Raa Atoll": ["Ungoofaaru", "Alifushi", "Dhuvaafaru"],
                "Shaviyani Atoll": ["Funadhoo", "Kanditheemu", "Maroshi"],
                "Thaa Atoll": ["Veymandoo", "Guraidhoo", "Kinbidhoo"],
                "Laamu Atoll": ["Fonadhoo", "Isdhoo", "Maavah"],
                "Gaafu Alifu Atoll": ["Viligili", "Kolamaafushi", "Dhevvadhoo"],
                "Gaafu Dhaalu Atoll": ["Thinadhoo", "Fiyori", "Gadhdhoo"],
                "Seenu Atoll": ["Addu City", "Hithadhoo", "Maradhoo", "Feydhoo"]
            },
            "Sri Lanka": {
                "Western Province": ["Colombo", "Negombo", "Kalutara"],
                "Southern Province": ["Galle", "Matara", "Hambantota"],
                "Central Province": ["Kandy", "Nuwara Eliya", "Matale"]
            },
            "India": {
                "Kerala": ["Kochi", "Thiruvananthapuram", "Kozhikode"],
                "Karnataka": ["Bengaluru", "Mysuru", "Mangaluru"],
                "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai"]
            },
            "Other": { "Other": ["Other"] }
        };

        var locationTreeCache = FALLBACK_LOCATION_TREE;
        var locationTreePromise = null;

        function getCurrentLocationTree() {
            return locationTreeCache || FALLBACK_LOCATION_TREE;
        }

        function applyLocationTree(data) {
            if (!data || typeof data !== 'object' || Array.isArray(data)) return getCurrentLocationTree();
            locationTreeCache = data;
            window.__vendorPortalLocationTree = data;
            try { window.sessionStorage.setItem('vendor_portal_location_tree_v1', JSON.stringify(data)); } catch (e) {}
            return locationTreeCache;
        }

        function getLocationTree() {
            if (window.__vendorPortalLocationTree) {
                locationTreeCache = window.__vendorPortalLocationTree;
                return Promise.resolve(locationTreeCache);
            }
            if (locationTreePromise) return locationTreePromise;
            locationTreePromise = new Promise(function (resolve) {
                try {
                    var cached = window.sessionStorage.getItem('vendor_portal_location_tree_v1');
                    if (cached) { resolve(applyLocationTree(JSON.parse(cached))); return; }
                } catch (e) {}
                fetch('/api/atoll-island/atolls', { cache: 'no-store' })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                    .then(function (atolls) {
                        if (!Array.isArray(atolls) || atolls.length === 0) throw new Error('empty');
                        var hasEmbeddedIslands = atolls.some(function (atoll) {
                            return Array.isArray(atoll && atoll.islands) && atoll.islands.length > 0;
                        });

                        if (hasEmbeddedIslands) {
                            var embeddedTree = {};
                            atolls.forEach(function (atoll) {
                                var atollName = String(atoll && atoll.name ? atoll.name : '').trim();
                                if (!atollName) return;
                                var islands = Array.isArray(atoll && atoll.islands)
                                    ? atoll.islands.map(function (i) { return String(i && i.name ? i.name : '').trim(); }).filter(Boolean)
                                    : [];
                                embeddedTree[atollName] = islands;
                            });
                            if (Object.keys(embeddedTree).length > 0) return embeddedTree;
                        }

                        return Promise.all(atolls.map(function (atoll) {
                            var id = Number(atoll && atoll.id ? atoll.id : 0);
                            var name = String(atoll && atoll.name ? atoll.name : '').trim();
                            if (!id || !name) return Promise.resolve(null);
                            return fetch('/api/atoll-island/atolls/' + id + '/islands', { cache: 'no-store' })
                                .then(function (r) { return r.ok ? r.json() : []; })
                                .then(function (islands) {
                                    return { atollName: name, islandNames: (Array.isArray(islands) ? islands : []).map(function (i) { return String(i && i.name ? i.name : '').trim(); }).filter(Boolean) };
                                })
                                .catch(function () { return { atollName: name, islandNames: [] }; });
                        })).then(function (rows) {
                            var fallbackTree = {};
                            (rows || []).forEach(function (row) { if (row && row.atollName) fallbackTree[row.atollName] = row.islandNames || []; });
                            return fallbackTree;
                        });
                    })
                    .then(function (tree) {
                        resolve(applyLocationTree(Object.keys(tree).length > 0 ? Object.assign({}, FALLBACK_LOCATION_TREE, { Maldives: tree }) : getCurrentLocationTree()));
                    })
                    .catch(function () { resolve(getCurrentLocationTree()); });
            });
            return locationTreePromise;
        }

        function rebuildSelect(sel, values, placeholder) {
            if (!sel) return;
            sel.innerHTML = '';
            var def = document.createElement('option'); def.value = ''; def.textContent = placeholder; sel.appendChild(def);
            values.forEach(function (v) { var o = document.createElement('option'); o.value = v; o.textContent = v; sel.appendChild(o); });
        }

        function rebuildCountrySelect(sel, selectedValue) {
            if (!sel) return;
            var current = String(selectedValue || sel.value || 'Maldives').trim() || 'Maldives';
            var options = COUNTRY_OPTIONS.slice();
            if (options.indexOf(current) === -1) {
                options.push(current);
            }
            sel.innerHTML = '';
            options.forEach(function (countryName) {
                var option = document.createElement('option');
                option.value = countryName;
                option.textContent = countryName;
                if (countryName === current) option.selected = true;
                sel.appendChild(option);
            });
        }

        function ensureSelectHasOption(sel, val) {
            if (!sel || !val) return;
            if (!Array.from(sel.options).some(function (o) { return o.value === val; })) {
                var o = document.createElement('option'); o.value = val; o.textContent = val; sel.appendChild(o);
            }
        }

        // ── Location cascade (create form) ───────────────────────────────────
        var locationCountry = document.getElementById('location_country');
        var locationState   = document.getElementById('location_state');
        var locationCity    = document.getElementById('location_city');
        var mapLatitude     = document.getElementById('map_latitude');
        var mapLongitude    = document.getElementById('map_longitude');
        var mapPlaceId      = document.getElementById('map_place_id');

        function refreshLocationSelectors() {
            if (!locationCountry || !locationState || !locationCity) return;
            var selectedCountry = locationCountry.dataset.selectedValue || locationCountry.value || 'Maldives';
            rebuildCountrySelect(locationCountry, selectedCountry);
            ensureSelectHasOption(locationCountry, selectedCountry);
            locationCountry.value = selectedCountry;
            var country = locationCountry.value || 'Maldives';
            var tree = getCurrentLocationTree();
            var states = Object.keys(tree[country] || {});
            var statePlaceholder = country === 'Maldives' ? 'Select atoll' : 'Select state/province';
            var cityPlaceholder = country === 'Maldives' ? 'Select island' : 'Select city';
            rebuildSelect(locationState, states, statePlaceholder);
            var selectedState = locationState.dataset.selectedValue || '';
            ensureSelectHasOption(locationState, selectedState);
            if (selectedState && Array.from(locationState.options).some(function (o) { return o.value === selectedState; })) {
                locationState.value = selectedState;
            } else { locationState.value = states[0] || ''; }
            var cities = (tree[country] || {})[locationState.value] || [];
            rebuildSelect(locationCity, cities, cityPlaceholder);
            var selectedCity = locationCity.dataset.selectedValue || '';
            ensureSelectHasOption(locationCity, selectedCity);
            if (selectedCity && Array.from(locationCity.options).some(function (o) { return o.value === selectedCity; })) {
                locationCity.value = selectedCity;
            } else if (cities.length > 0) { locationCity.value = cities[0]; }
            locationCountry.dataset.selectedValue = '';
            locationState.dataset.selectedValue = '';
            locationCity.dataset.selectedValue = '';
        }

        if (locationCountry && locationState && locationCity) {
            locationCountry.dataset.selectedValue = locationCountry.dataset.selectedValue || 'Maldives';
            refreshLocationSelectors();
            getLocationTree().then(refreshLocationSelectors);
            locationCountry.addEventListener('change', refreshLocationSelectors);
            locationState.addEventListener('change', function () {
                var country = locationCountry.value || 'Maldives';
                var tree = getCurrentLocationTree();
                var cities = (tree[country] || {})[locationState.value] || [];
                var cityPlaceholder = country === 'Maldives' ? 'Select island' : 'Select city';
                rebuildSelect(locationCity, cities, cityPlaceholder);
                if (cities.length > 0) locationCity.value = cities[0];
            });
        }

        // ── Map picker (create form) ─────────────────────────────────────────
        var COUNTRY_MAP_CENTER = { 'maldives': [3.2028, 73.2207, 8], 'sri lanka': [7.8731, 80.7718, 8], 'india': [20.5937, 78.9629, 5] };

        function initLocationMap() {
            if (!window.L) return;
            var mapEl = document.getElementById('propertyMap');
            if (!mapEl) return;
            var defaultLat = Number(mapLatitude && mapLatitude.value) || 4.1755;
            var defaultLng = Number(mapLongitude && mapLongitude.value) || 73.5093;
            var map = window.L.map(mapEl, { preferCanvas: true, zoomControl: true, fadeAnimation: false }).setView([defaultLat, defaultLng], 11);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            var marker = window.L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
            function updateFromMap(latlng) {
                var lat = Number(latlng.lat.toFixed(6)), lng = Number(latlng.lng.toFixed(6));
                if (mapLatitude) mapLatitude.value = String(lat);
                if (mapLongitude) mapLongitude.value = String(lng);
                if (mapPlaceId && !mapPlaceId.value) mapPlaceId.value = 'PIN-' + lat + ',' + lng;
                marker.setLatLng([lat, lng]);
            }
            map.on('click', function (e) { updateFromMap(e.latlng); });
            marker.on('dragend', function () { updateFromMap(marker.getLatLng()); });
            setTimeout(function () { map.invalidateSize(); }, 180);
        }

        // ── Edit form map pickers ────────────────────────────────────────────
        function initEditMap(mapWrapEl, latInput, lngInput) {
            if (!window.L || !mapWrapEl) return;
            var defaultLat = Number(latInput && latInput.value) || 4.1755;
            var defaultLng = Number(lngInput && lngInput.value) || 73.5093;
            var map = window.L.map(mapWrapEl, { preferCanvas: true, zoomControl: true, fadeAnimation: false }).setView([defaultLat, defaultLng], 11);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            var marker = window.L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
            function updateCoords(latlng) {
                var lat = Number(latlng.lat.toFixed(6)), lng = Number(latlng.lng.toFixed(6));
                if (latInput) latInput.value = String(lat);
                if (lngInput) lngInput.value = String(lng);
                marker.setLatLng([lat, lng]);
            }
            map.on('click', function (e) { updateCoords(e.latlng); });
            marker.on('dragend', function () { updateCoords(marker.getLatLng()); });
            setTimeout(function () { map.invalidateSize(); }, 180);
        }

        // ── Edit form location cascade ───────────────────────────────────────
        var editLocationCountry = document.querySelector('[data-edit-country]');
        var editLocationState   = document.querySelector('[data-edit-state]');
        var editLocationCity    = document.querySelector('[data-edit-city]');

        function refreshEditLocationSelectors() {
            if (!editLocationCountry || !editLocationState || !editLocationCity) return;
            var selectedCountry = editLocationCountry.dataset.selectedValue || editLocationCountry.value || 'Maldives';
            rebuildCountrySelect(editLocationCountry, selectedCountry);
            ensureSelectHasOption(editLocationCountry, selectedCountry);
            editLocationCountry.value = selectedCountry;
            var country = editLocationCountry.value || 'Maldives';
            var tree = getCurrentLocationTree();
            var states = Object.keys(tree[country] || {});
            var editStatePlaceholder = country === 'Maldives' ? 'Select atoll' : 'Select state/province';
            var editCityPlaceholder = country === 'Maldives' ? 'Select island' : 'Select city';
            rebuildSelect(editLocationState, states, editStatePlaceholder);
            var selectedState = editLocationState.dataset.selectedValue || '';
            ensureSelectHasOption(editLocationState, selectedState);
            if (selectedState && Array.from(editLocationState.options).some(function (o) { return o.value === selectedState; })) {
                editLocationState.value = selectedState;
            } else { editLocationState.value = states[0] || ''; }
            var cities = (tree[country] || {})[editLocationState.value] || [];
            rebuildSelect(editLocationCity, cities, editCityPlaceholder);
            var selectedCity = editLocationCity.dataset.selectedValue || '';
            ensureSelectHasOption(editLocationCity, selectedCity);
            if (selectedCity && Array.from(editLocationCity.options).some(function (o) { return o.value === selectedCity; })) {
                editLocationCity.value = selectedCity;
            } else if (cities.length > 0) { editLocationCity.value = cities[0]; }
            editLocationCountry.dataset.selectedValue = '';
            editLocationState.dataset.selectedValue = '';
            editLocationCity.dataset.selectedValue = '';
        }

        if (editLocationCountry && editLocationState && editLocationCity) {
            refreshEditLocationSelectors();
            getLocationTree().then(refreshEditLocationSelectors);
            editLocationCountry.addEventListener('change', refreshEditLocationSelectors);
            editLocationState.addEventListener('change', function () {
                var country = editLocationCountry.value || 'Maldives';
                var tree = getCurrentLocationTree();
                var cities = (tree[country] || {})[editLocationState.value] || [];
                var editCityPlaceholder = country === 'Maldives' ? 'Select island' : 'Select city';
                rebuildSelect(editLocationCity, cities, editCityPlaceholder);
                if (cities.length > 0) editLocationCity.value = cities[0];
            });
        }

        // ── Init maps ────────────────────────────────────────────────────────
        initLocationMap();

        // Edit page map: look for [data-edit-map-for-lat] and [data-edit-map-wrap]
        var editMapWrap = document.querySelector('[data-edit-map-wrap]');
        if (editMapWrap) {
            var editLatInput = document.querySelector('[name="map_latitude"]');
            var editLngInput = document.querySelector('[name="map_longitude"]');
            initEditMap(editMapWrap, editLatInput, editLngInput);
        }
    }());
    </script>
</body>
</html>
