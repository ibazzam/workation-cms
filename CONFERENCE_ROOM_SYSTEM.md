# Conference Room Facilities & Packages System

## Overview

The conference room feature now includes a sophisticated pricing and packaging system that allows vendors to:
1. **Offer pre-configured packages** (Basic, Standard, Premium tiers)
2. **Sell individual facilities** with flexible pricing models
3. **Support multi-day events** with automatic price scaling

## Database Schema

### Tables

#### `conference_rooms`
- Base room information (name, capacity, location, hourly rate)
- Vendor assignment
- Basic pricing

#### `conference_room_facilities`
- Individual facilities/services available at the room
- Flexible pricing types (hourly, per-unit, per-pax, per-meal, flat)
- Availability tracking
- Categories: equipment, refreshment, catering, service

#### `conference_room_packages`
- Pre-built bundles (3-day basic, standard, premium, custom)
- Bundle-specific pricing and discounts
- Duration (1-3+ days)
- Included facilities (many-to-many relationship)

#### `package_facility` (Pivot Table)
- Links facilities to packages
- Many-to-many relationship

## Pricing Types Explained

### 1. **Hourly** - Charged per hour
```
Cost = price_per_hour × hours_booked
Example: Video Conferencing System @ MVR 50/hour × 8 hours = MVR 400
```

### 2. **Per Unit** - Charged per quantity (equipment)
```
Cost = price_per_unit × quantity_ordered
Example: Additional Chairs @ MVR 25/unit × 10 chairs = MVR 250
```

### 3. **Per Pax** - Charged per person (fixed benefit)
```
Cost = price_per_person × attendee_count
Example: Coffee & Tea @ MVR 50/pax × 30 people = MVR 1,500
```

### 4. **Per Meal** - Charged per person per day (catering)
```
Cost = price_per_person × attendee_count × number_of_days
Example: Breakfast @ MVR 150/person/day × 30 people × 3 days = MVR 13,500
```

### 5. **Flat** - Single charge regardless
```
Cost = fixed_price
Example: Event Coordinator @ MVR 200 (full event)
```

## Package Categories

### Basic Package (1 Day)
- **Cost**: MVR 3,000
- **Includes**: Hall + WiFi + Projector + Whiteboard
- **Best for**: Small meetings, quick seminars
- **Suitable capacity**: Up to 30 people

### Standard Package (3 Days)  
- **Base Cost**: MVR 9,500 (with 5% discount = MVR 9,025)
- **Includes**:
  - All basic equipment
  - Video Conferencing System
  - Daily coffee & tea
  - Breakfast buffet
  - Lunch buffet
  - Afternoon snacks
  - Event Coordinator (9am-6pm)
- **Best for**: Training programs, workshops
- **Suitable capacity**: 20-50 people

### Premium Package (3 Days)
- **Base Cost**: MVR 15,000 (with 10% discount = MVR 13,500)
- **Includes**: Everything + Premium dinner for all 3 days
- **Best for**: International conferences, executive retreats
- **Suitable capacity**: 30+ people

## Customer Booking Workflow

### Step 1: View Package Options
```
Customer sees 3 pre-configured packages + "À La Carte" option
```

### Step 2: Choose Approach
- **Package Route**: Select a package → Automatic facility inclusion
- **À La Carte Route**: Select individual facilities → Custom pricing

### Step 3: Add-ons (if needed)
- Extra chairs/tables
- Premium services
- Extended hours

### Step 4: Dynamic Price Calculation
```
Total = Room Rate + Package/Facilities Cost
        - Discounts (if package)
        × Scaling factors (pax, days, hours)
```

### Step 5: Confirmation
System displays itemized breakdown:
- Conference Room (base)
- Facilities breakdown
- Total cost

## Models & Relationships

### ConferenceRoom
```php
public function facilities(): HasMany
public function packages(): HasMany
public function getAvailableFacilities()
public function getActivePackages()
```

### ConferenceRoomFacility
```php
public function conferenceRoom(): BelongsTo
public function calculatePrice(
    int $quantity = 1,
    int $hours = 1, 
    int $days = 1, 
    int $pax = 1
): float
```

### ConferenceRoomPackage
```php
public function conferenceRoom(): BelongsTo
public function facilities(): BelongsToMany
public function calculateTotalPrice(int $pax = 1): float
public function getSummary(): string
```

## Example Calculations

### Scenario 1: À La Carte for 2-Day Meeting
- Conference Room: MVR 500 (base)
- Video Conferencing: MVR 50 × 8 hours × 2 days = MVR 800
- Coffee & Tea: MVR 50 × 20 people = MVR 1,000
- Lunch Buffet: MVR 300 × 20 people × 2 days = MVR 12,000
- **Total**: MVR 14,300

### Scenario 2: Standard Package (Better Value)
- 3-Day Standard Package: MVR 9,025 (already discounted)
- Includes all facilities + 3 days coverage
- **Savings**: ~MVR 5,000 vs custom selection

### Scenario 3: Premium Package with Extensions
- 3-Day Premium Package: MVR 13,500 (10% discount applied)
- Additional Chairs: MVR 25 × 5 = MVR 125
- Extra Video Conference Hours: MVR 50 × 2 = MVR 100
- **Total**: MVR 13,725

## Seeded Sample Data

### Facilities (26 total)
- **Equipment**: Projector, Whiteboard, WiFi, Video Conferencing, Chairs, Tables
- **Refreshments**: Coffee & Tea, Snacks
- **Catering**: Breakfast, Lunch, Dinner
- **Services**: Event Coordinator, AV Technician

### Packages (3)
- Basic (1-day, MVR 3,000)
- Standard (3-day, MVR 9,500 → 9,025 with 5% discount)
- Premium (3-day, MVR 15,000 → 13,500 with 10% discount)

## Usage in Code

### In Blade Templates
```blade
@include('partials.conference-room-booking')
```

### Passing Room to View
```php
$room = ConferenceRoom::findOrFail($id);
return view('booking', ['conferenceRoom' => $room]);
```

### Get Available Packages
```php
$packages = $room->getActivePackages();

foreach ($packages as $package) {
    echo $package->name;
    echo $package->calculateTotalPrice(pax: 50);
    echo $package->getSummary();
}
```

### Calculate Individual Facility Cost
```php
$facility = ConferenceRoomFacility::find($id);
$cost = $facility->calculatePrice(
    quantity: 5,      // chairs
    hours: 8,         // for hourly items
    days: 3,          // for multi-day
    pax: 30           // attendees
);
```

## Vendor Management

Vendors can:
1. **Create custom packages** by selecting existing facilities
2. **Set discount percentages** (e.g., 10% for 3-day bookings)
3. **Mark facilities as free or paid** per room
4. **Adjust pricing** per facility per room
5. **Add vendor notes** for special instructions

## API Endpoints (Future)

```
GET  /api/conference-rooms/{id}/packages
GET  /api/conference-rooms/{id}/facilities
POST /api/conference-room-bookings
GET  /api/conference-room-bookings/{id}/pricing-summary
```

## Display in Customer Catalog

The booking form includes:
- **Package cards** with visual indicators (✓ basic, ✨ standard, ⭐ premium)
- **Facilities grid** organized by category
- **Live price calculation** (updates as selections change)
- **Itemized summary** showing breakdown
- **Responsive design** for mobile & desktop

## Migration Steps

```bash
# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed --class=ConferenceRoomFacilitySeeder

# Test pricing calculations
php artisan tinker
> $room = App\Models\ConferenceRoom::first();
> $room->getActivePackages()->first()->calculateTotalPrice(pax: 50);
```

## Notes

- Prices are flexible and can be customized per vendor/room
- Packages automatically scale for different attendee counts (where applicable)
- All calculations include currency handling (default MVR)
- Discounts are applied at package level, not individual items
- Free items never incur charges, just show in summary
