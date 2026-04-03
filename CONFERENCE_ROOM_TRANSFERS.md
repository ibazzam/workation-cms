# Conference Room Transfer Integration System

## Overview

The transfer integration system allows resort-based conference rooms to offer transportation services to guests. This includes airport pickups/drop-offs, inter-island transfers, speedboat charters, and complimentary resort shuttles.

## Components

### 1. ConferenceRoomTransferOption Model

**Fields:**
- `conference_room_id` - Link to conference room
- `transfer_type` - Type of transfer (enum: airport_pickup, airport_dropoff, airport_roundtrip, inter_island, speedboat, resort_shuttle, custom)
- `origin_location` - Starting point (e.g., "Malé International Airport")
- `destination_location` - End point (e.g., "Resort Conference Hall")
- `description` - Service description
- `price_per_person` - Cost per passenger
- `group_size_min/max` - Capacity constraints
- `duration_minutes` - Estimated travel time
- `availability` - Schedule (daily, weekdays, weekends, seasonal)
- `is_active` - Enable/disable option

**Methods:**
```php
public function calculateTransferCost(int $pax, int $roundTrip): array
// Returns: per_person, group_size, trips, total, subtotal_display

public function isAvailableForGroupSize(int $pax): bool
// Checks min/max group size constraints

public function getTypeLabel(): string
// Returns emoji + readable label (e.g., "✈️ Airport Pickup")
```

### 2. ConferenceRoom Model Updates

**New Fields:**
- `is_resort_venue` - Boolean flag for resort locations
- `resort_name` - Name of resort (e.g., "Tropical Paradise Resort")
- `airport_name` - Nearest airport (e.g., "Malé International Airport")

**New Methods:**
```php
public function transferOptions(): HasMany
// Get all transfer options

public function getAvailableTransfers()
// Get active (enabled) transfers only

public function hasTransferOptions(): bool
// Quick check for resort + has active transfers
```

## Transfer Types Explained

### 1. **Airport Pickup** ✈️
```
Single transfer from airport to resort
- Price: MVR 250/person
- Duration: 45 minutes
- Available: Daily, all year
```

### 2. **Airport Drop-off** ✈️
```
Return transfer from resort to airport
- Price: MVR 250/person
- Duration: 45 minutes
- Available: Daily, all year
```

### 3. **Airport Round-trip** ✈️
```
Combined pickup + drop-off (2 transfers)
- Price: MVR 450/person (includes both directions)
- Duration: 90 minutes total
- Available: Daily, all year
```

### 4. **Inter-island Transfer** 🚤
```
Transfer between nearby islands
- Price: MVR 150/person
- Duration: 30 minutes
- Group: 1-30 people
- Available: Daily
```

### 5. **Speedboat Charter** ⚡
```
Premium speedboat for excursions/tours
- Price: MVR 500/person
- Duration: 2 hours
- Group: 5-20 people (minimum 5)
- Available: Daily
```

### 6. **Resort Shuttle** 🚐
```
Complimentary shuttle between resort areas
- Price: FREE
- Duration: 10 minutes
- Group: Up to 50 people
- Available: Daily
```

## Seeded Sample Data

The `ConferenceRoomFacilitySeeder` includes 6 transfer options for "Tropical Paradise Resort":

1. **Airport Pickup** - MVR 250/person
2. **Airport Drop-off** - MVR 250/person  
3. **Airport Round-trip** - MVR 450/person
4. **Inter-island Transfer** - MVR 150/person
5. **Resort Shuttle** - FREE (complimentary)
6. **Speedboat Charter** - MVR 500/person (min 5 people)

## Pricing Calculations

### Example 1: Single Airport Pickup
```
Transfer: Airport → Resort
Passengers: 8 people
Price: MVR 250 × 8 = MVR 2,000
```

### Example 2: Round-trip for Team
```
Transfer: Airport (pickup) + Airport (drop-off)
Passengers: 20 people
Price: MVR 450 × 20 = MVR 9,000
(Includes both directions)
```

### Example 3: Multi-Transfer Booking
```
Option 1: Airport Pickup - MVR 250 × 25 = MVR 6,250
Option 2: Resort Shuttle - FREE
Option 3: Speedboat Excursion - MVR 500 × 25 = MVR 12,500
Total Transfer Cost: MVR 18,750
```

## UI Component: `conference-room-transfers.blade.php`

### Features
- **Transfer Cards** - Visual selection with hover effects
- **Route Display** - Origin → Destination with icons
- **Duration Badge** - Estimated travel time
- **Group Size Selector** - Dropdown to select number of passengers
- **Trip Options** - Radio buttons for one-way vs round-trip
- **Live Pricing** - Updates as selections change
- **Free Badge** - Highlights complimentary services (resort shuttle)
- **Live Total** - Itemized transfer cost summary

### Integration in Booking Form
```blade
@include('partials.conference-room-transfers')
```

### Display Logic
Shows transfer section only if:
1. `$conferenceRoom->is_resort_venue === true`
2. Room has active transfer options

## Database Schema

### conference_room_transfer_options Table
```
id (PK)
conference_room_id (FK)
transfer_type (ENUM)
origin_location (STRING)
destination_location (STRING)
description (TEXT)
price_per_person (DECIMAL)
group_size_min (INT)
group_size_max (INT)
duration_minutes (INT)
availability (STRING)
is_active (BOOLEAN)
created_at, updated_at
```

### Indexes
- conference_room_id
- transfer_type
- is_active

## Vendor Features

Resort vendors can:
1. **Add multiple transfer types** (airport, inter-island, etc.)
2. **Set custom pricing** per transfer type
3. **Configure group size limits** (min 1, max 50, etc.)
4. **Set duration estimates** for UI display
5. **Enable/disable options** without deleting
6. **Offer free transfers** (resort shuttle)
7. **Mark seasonal availability** (daily/weekdays/seasonal)

## Usage in Code

### Get Transfer Options
```php
$room = ConferenceRoom::findOrFail($id);
$transfers = $room->getAvailableTransfers();

foreach ($transfers as $transfer) {
    echo $transfer->getTypeLabel();
    echo $transfer->price_per_person;
    echo $transfer->duration_minutes . " min";
}
```

### Calculate Transfer Cost
```php
$transfer = ConferenceRoomTransferOption::find($id);

$cost = $transfer->calculateTransferCost(
    pax: 25,
    roundTrip: 2 // 1 for one-way, 2 for round-trip
);

echo $cost['total']; // MVR 22,500
echo $cost['subtotal_display']; // "MVR 450 × 25 persons × 2 trips"
```

### Check Availability
```php
if ($transfer->isAvailableForGroupSize(8)) {
    // Show option
}
```

### Display in Blade
```blade
@if($room->hasTransferOptions())
    @include('partials.conference-room-transfers')
@endif
```

## Booking Summary Integration

Transfer costs are included in the booking summary:
```
Conference Room: MVR 500
Facilities: MVR 5,000
SUBTOTAL: MVR 5,500

Transfers:
  + Airport Pickup (25 people): MVR 6,250
  + Resort Shuttle: FREE
  + Speedboat Charter (25 people): MVR 12,500
TRANSFERS TOTAL: MVR 18,750

GRAND TOTAL: MVR 24,250
```

## API Endpoints (Future)

```
GET  /api/conference-rooms/{id}/transfers
POST /api/conference-room-bookings/{id}/transfers
GET  /api/conference-room-transfers/{id}/calculate-cost
```

Request:
```json
{
  "transfer_id": 5,
  "passengers": 25,
  "type": "roundtrip"
}
```

Response:
```json
{
  "transfer": "Airport Pickup + Drop-off",
  "per_person": 450,
  "passengers": 25,
  "trips": 2,
  "total": 22500,
  "currency": "MVR",
  "availability": "daily"
}
```

## Migration & Setup

```bash
# Run migration (adds transfer_options table + resort fields)
php artisan migrate

# Seed sample data
php artisan db:seed --class=ConferenceRoomFacilitySeeder

# Verify in tinker
php artisan tinker
> $room = App\Models\ConferenceRoom::first();
> $room->hasTransferOptions(); // true (if resort)
> $room->getAvailableTransfers()->count(); // 6
```

## Notes

- Transfers are **optional per room** (only for resort venues)
- **Pricing is flexible** (vendors set per-person rates)
- **Group size constraints** ensure vehicle capacity
- **Free options** supported (resort shuttle)
- **Round-trip logic** built-in for airport transfers
- **Availability tracking** for seasonal services
- Integrates seamlessly with facility pricing
- All costs calculated **per passenger** for transparency
- **Customer can mix multiple transfers** in one booking

## Example Resort Workflow

1. Customer selects "Tropical Paradise Resort Conference Room"
2. Booking form shows:
   - Conference packages (3-day, meals, AV)
   - Facilities (chairs, catering, services)
   - **Transfer options** (airport, inter-island, shuttle)
3. Customer selects:
   - Standard 3-day package (MVR 9,025)
   - Airport round-trip for 20 people (MVR 9,000)
   - Speedboat excursion for team building (MVR 10,000)
4. Booking summary shows itemized breakdown
5. Total: MVR 28,025 (room + facilities + transfers)
