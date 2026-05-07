# Sea Transport & Liveaboard Features - Implementation Summary

**Date:** May 7, 2026  
**Status:** ✅ Core implementation complete - Integration pending

---

## ✅ COMPLETED COMPONENTS

### 1. **Vendor Portal Forms** (4 files created)
- **Sea Transport Create:** `resources/views/vendor-portal/partials/forms/create/sea_transport.blade.php`
  - Route name, vessel info, departure/arrival points, times, total seats
  - Local (MVR) & foreign (USD) pricing per seat
  - Operating schedule/availability configuration
  
- **Sea Transport Edit:** `resources/views/vendor-portal/partials/forms/edit/sea_transport.blade.php`
  - Same fields as create form with data population from existing listing

- **Liveaboard Create:** `resources/views/vendor-portal/partials/forms/create/liveaboard.blade.php`
  - Journey name, start/end points, duration
  - Stopovers with boarding/disembarking permissions
  - Pricing matrix (From→To=Price format)
  - Vessel & contact info

- **Liveaboard Edit:** `resources/views/vendor-portal/partials/forms/edit/liveaboard.blade.php`
  - Same as create with data population

### 2. **Backend Route Handlers** (vendor-operations.php)
Added to `vendorPortalBuildPropertyDetails()`:
- **Sea Transport:** Parses vessel name, route points, times, seats, pricing, availability schedule
- **Liveaboard:** Parses stopovers (JSON array with embark/disembark flags), pricing matrix, vessel info

Added to `vendorPortalValidatePropertyDetails()`:
- **Sea Transport validation:** Departure/arrival points, times, trip duration, seat count, pricing
- **Liveaboard validation:** Route points, duration, stopovers presence, pricing matrix presence

### 3. **Booking UI Components** (2 modals created)
- **Seat Selector:** `resources/views/partials/seat-selector.blade.php`
  - Grid-based seat display (4 seats per row)
  - Visual distinction: Available (white), Occupied (red), Selected (blue)
  - Shows selected seat count and list
  - Multi-seat selection with confirmation
  - JavaScript functions: `initializeSeatSelector()`, `toggleSeat()`, `confirmSeatSelection()`

- **Boarding Point Selector:** `resources/views/partials/boarding-point-selector.blade.php`
  - Dual dropdowns: Boarding point & Disembarking point
  - Dynamic stopover filtering based on embark/disembark permissions
  - Real-time pricing lookup from pricing matrix
  - Journey overview display
  - JavaScript functions: `initializeBoardingPointSelector()`, `confirmBoardingPointSelection()`

---

## ⏳ NEXT STEPS: INTEGRATION

### 1. **Update Catalog Listing Views**
   - Add sea_transport route cards to catalog/search results (similar to excursion/water_sports)
   - Add liveaboard cards to accommodation search results
   - Display: route name, times, available seats/price, select button

### 2. **Update Booking Routes** (`routes/web/booking.php`)
   - Add `sea_transport` case to booking controller that loads seat selector
   - Add `liveaboard` case that loads boarding point selector
   - Pass pricing config (total_seats, local/foreign prices, pricing_matrix, stopovers)

### 3. **Update category-booking.blade.php**
   - Add sea_transport section: Show "Select Seats" button → triggers seat selector modal
   - Add liveaboard section: Show boarding/disembarking selector (embedded or modal)
   - Store selections in hidden inputs (`selected_seats`, `boarding_point`, `disembark_point`)

### 4. **Update Checkout Flow** (`checkout/{id}/transfer`)
   - For sea_transport: Skip transfer selection (seats already selected), go straight to payment
   - For liveaboard: Skip transfer selection, use pricing_matrix[boarding→disembark] for total
   - Validate that selections exist before proceeding

### 5. **Update Reservation Model**
   - Add fields: `selected_seats` (JSON), `boarding_point`, `disembark_point` to reservations table
   - Update pricing calculation to use per-seat/per-package models

### 6. **Create Confirmation Pages**
   - Show selected seats in confirmation for sea_transport
   - Show boarding/disembarking points in confirmation for liveaboard

---

## 📝 DATA STRUCTURES

### Sea Transport (listing_details JSON)
```json
{
  "vessel_name": "Island Express",
  "registration_no": "REG123",
  "departure_point": "Malé",
  "arrival_point": "Seenu Gan",
  "departure_time": "08:00",
  "return_time": "17:00",
  "trip_duration_minutes": 540,
  "total_seats": 30,
  "local_price": 500,
  "foreign_price": 50,
  "contact_name": "Ahmed",
  "contact_number": "+960 1234567",
  "boarding_instructions": "Arrive 30 min early",
  "availability_schedule": ["Mon", "Wed", "Fri", "2026-05-15"]
}
```

### Liveaboard (listing_details JSON)
```json
{
  "start_point": "Malé",
  "end_point": "Seenu Gan",
  "journey_duration_days": 4,
  "vessel_name": "Safari Vessel",
  "registration_no": "REG456",
  "cabin_count": 8,
  "stopovers": [
    {"name": "Laamu Kahdhoo", "allow_embark": true, "allow_disembark": true},
    {"name": "Gan Island", "allow_embark": true, "allow_disembark": true}
  ],
  "pricing_matrix": {
    "Malé→Seenu Gan": 5000,
    "Malé→Gan Island": 4500,
    "Laamu Kahdhoo→Seenu Gan": 3500,
    "Laamu Kahdhoo→Malé": 3000
  }
}
```

### Reservation Fields to Add
```
selected_seats: JSON array [1, 3, 5]  // for sea_transport
boarding_point: "Laamu Kahdhoo"       // for liveaboard
disembark_point: "Seenu Gan"          // for liveaboard
```

---

## 🔧 INTEGRATION CHECKLIST

- [ ] Database migration: Add `selected_seats`, `boarding_point`, `disembark_point` to reservations
- [ ] Update `routes/web/booking.php` to handle sea_transport & liveaboard cases
- [ ] Update `category-booking.blade.php` to include seat/boarding selectors
- [ ] Update catalog views to list sea_transport & liveaboard properties
- [ ] Update checkout pricing logic for per-seat and pricing_matrix models
- [ ] Create/update confirmation templates
- [ ] Add tests for both booking flows
- [ ] Update vendor portal routing to use new form files

---

## 🧪 TESTING POINTS

1. **Vendor Portal:**
   - Create sea_transport route with pricing
   - Create liveaboard with stopovers and pricing matrix
   - Verify forms save and edit correctly

2. **Booking Catalog:**
   - View sea_transport routes in search
   - View liveaboard in accommodation search
   - Pricing displays correctly

3. **Seat Selector:**
   - Select multiple seats
   - Occupied seats cannot be clicked
   - Selected seats persist through checkout

4. **Boarding Point Selector:**
   - Only allowed boarding points visible
   - Only allowed disembarking points visible
   - Pricing updates based on selection
   - Selection persists through checkout

5. **Checkout:**
   - Correct pricing applied
   - Confirmation shows selections
   - Reservation saved with seat/boarding data

---

## 📋 FILES CREATED/MODIFIED

**Created:**
1. `resources/views/vendor-portal/partials/forms/create/sea_transport.blade.php`
2. `resources/views/vendor-portal/partials/forms/edit/sea_transport.blade.php`
3. `resources/views/vendor-portal/partials/forms/create/liveaboard.blade.php`
4. `resources/views/vendor-portal/partials/forms/edit/liveaboard.blade.php`
5. `resources/views/partials/seat-selector.blade.php`
6. `resources/views/partials/boarding-point-selector.blade.php`

**Modified:**
1. `routes/vendor-operations.php` - Added handlers for both categories

---

## 🎯 FEATURE DETAILS

### Sea Transport (Ferries)
- **Booking Model:** Direct seat selection from catalog
- **Pricing:** Per-seat uniform pricing (varies by local/foreign)
- **Flow:** Catalog → Seat selector → Checkout → Confirmation
- **No detail page:** Direct from catalog to seat selection

### Liveaboard/Safari
- **Booking Model:** Boarding + disembarking point selection
- **Pricing:** Package pricing matrix based on route (boarding→disembark)
- **Flow:** Catalog → Boarding selector → Checkout → Confirmation
- **Route mapping:** Start → Stopovers → End points visualized in selector

---

**Ready for integration testing!**
