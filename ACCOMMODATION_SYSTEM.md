# Accommodation System Documentation

## Overview

The **Accommodation System** provides a comprehensive backend framework for managing hotel/resort rooms with flexible amenity pricing, pre-built packages, and dynamic pricing calculations. This system mirrors the robustness of the conference room booking system.

**Key Features:**
- Multi-tier room types (single/double/triple/suite/villa)
- Flexible amenity pricing with 5 distinct models
- Pre-configured accommodation packages (2-4 day bundles)
- Automatic price calculations with discounts
- Vendor-driven amenity configuration
- Full guest occupancy handling

---

## Data Models

### 1. AccommodationRoom Model

Represents individual room types within a property.

**Table: `accommodation_rooms`**
```php
$table->id();
$table->foreignId('property_id')->constrained('vendor_properties');
$table->enum('room_type', ['single', 'double', 'triple', 'suite', 'villa']);
$table->unsignedInteger('capacity_guests');
$table->unsignedInteger('total_rooms_available');
$table->decimal('base_price_per_night', 12, 2);
$table->string('currency')->default('MVR');
$table->text('description');
$table->unsignedInteger('max_occupancy');
$table->boolean('is_active');
```

**Key Methods:**
- `calculateNightlyRate(int $guests): float` - Computes nightly rate with per-guest surcharges
- `calculateStayCost(int $nights, int $guests, array $amenityIds): array` - Full pricing breakdown
- `getCapacityLabel(): string` - Human-readable room type description

**Example Usage:**
```php
$room = AccommodationRoom::find(1); // Double room
$cost = $room->calculateStayCost(nights: 3, guests: 2, amenityIds: [5, 11]);
// Returns: ['nightly_rate' => 450, 'room_subtotal' => 1350, 'amenity_total' => 600, 'grand_total' => 1950]
```

---

### 2. RoomAmenity Model

Individual amenities/services that can be added to rooms or included in packages.

**Table: `room_amenities`**
```php
$table->id();
$table->foreignId('property_id')->constrained('vendor_properties');
$table->string('name', 100);
$table->text('description');
$table->enum('amenity_category', [
    'connectivity', 'dining', 'wellness', 'transport', 'services', 'recreation', 'parking'
]);
$table->enum('pricing_type', [
    'nightly',              // Price per night
    'per_pax',              // Fixed price per guest
    'per_pax_per_night',    // Price per guest per night
    'one_time',             // Single charge
    'flat'                  // Fixed amount
]);
$table->decimal('price', 12, 2);
$table->boolean('is_included_in_base');
$table->boolean('is_active');
```

**Pricing Types Explained:**

| Type | Formula | Example |
|------|---------|---------|
| `nightly` | price × nights | WiFi: 50 MVR/night × 3 = 150 MVR |
| `per_pax` | price × guests | Spa: 250 MVR/person × 2 = 500 MVR |
| `per_pax_per_night` | price × guests × nights | Breakfast: 200 MVR/pax/night × 2 × 3 = 1,200 MVR |
| `one_time` | price | Late checkout: 200 MVR |
| `flat` | price | Airport transfer: 300 MVR |

**Amenity Categories:**
- 📡 **connectivity** - WiFi, Internet, TV, Streaming
- 🍽️ **dining** - Breakfast, Dinner, Room Service, Minibar
- 🧘 **wellness** - Spa, Massage, Yoga, Gym
- 🚗 **transport** - Airport transfer, Shuttle, Speedboat, Rental
- 🔧 **services** - Late checkout, Early checkin, Laundry
- 🎮 **recreation** - Water sports, Snorkeling, Activities
- 🅿️ **parking** - Standard parking, Valet parking

**Key Methods:**
- `calculatePrice(int $nights, int $guests): float` - Implements pricing formula
- `getCategoryLabel(): string` - Returns emoji + category name
- `getPricingLabel(): string` - Returns human-readable pricing model

**Example Amenities (24 Seeded):**
```
Connectivity (5):  WiFi, Fiber Internet, TV/Cable, Smart TV, Bluetooth Speaker
Dining (6):        Continental Breakfast, Full Breakfast, Dinner, All-Inclusive, Room Service, Minibar
Wellness (6):      Spa Access, Massage, Yoga, Meditation, Gym, Sauna
Transport (4):     Airport Transfer, Island Shuttle, Speedboat Charter, Bicycle Rental
Services (3):      Late Checkout, Early Checkin, Laundry
Parking (2):       Resort Parking, Valet Parking
Recreation (2):    Water Sports, Snorkeling Equipment
```

---

### 3. AccommodationPackage Model

Pre-configured room packages with included amenities and automatic discounts.

**Table: `accommodation_packages`**
```php
$table->id();
$table->foreignId('property_id')->constrained('vendor_properties');
$table->foreignId('room_id')->constrained('accommodation_rooms');
$table->string('package_name', 160);
$table->text('description');
$table->unsignedInteger('duration_nights');
$table->decimal('base_price', 12, 2);
$table->unsignedInteger('discount_percentage');
$table->string('currency')->default('MVR');
$table->boolean('is_active');
```

**Key Methods:**
- `calculatePackagePrice(int $guests): array` - Full pricing breakdown
- `getSummary(): string` - Package description with included amenities
- `getValueProposition(): array` - Per-night pricing and savings

**Package Pricing Logic:**
```
Total = (base_room_price + included_amenities_cost) - discount
Final Price Per Night = Total / duration_nights
```

**Seeded Packages (3-Tier):**

| Package | Nights | Room Type | Amenities | Base Price | Discount | Final Price |
|---------|--------|-----------|-----------|-----------|----------|------------|
| Weekend Escape | 2 | Double | WiFi, Breakfast, Gym, Parking | 800 MVR | 5% | 760 MVR |
| Relaxation Retreat | 3 | Triple | Full Breakfast, Spa, Gym, Sauna | 2,500 MVR | 10% | 2,250 MVR |
| Ultimate Luxury | 4 | Villa | All Premium Amenities (14 items) | 6,000 MVR | 15% | 5,100 MVR |

---

### 4. Pivot Tables

**`room_amenity`** - Many-to-many relationship between rooms and amenities
```php
room_id (FK) | amenity_id (FK) | timestamps
```

**`package_amenity`** - Many-to-many relationship between packages and included amenities
```php
package_id (FK) | amenity_id (FK) | timestamps
```

---

## Pricing Calculations

### Example 1: Basic Stay (Double Room, 3 nights, 2 guests)

**Room Only:**
```
Nightly Rate = 450 MVR (base for 2 guests)
Room Total = 450 × 3 = 1,350 MVR

Amenities Selected:
- Breakfast (per_pax_per_night): 200 × 2 × 3 = 1,200 MVR
- Airport Transfer (per_pax): 300 × 2 = 600 MVR
- Spa Access (per_pax): 250 × 2 = 500 MVR

Grand Total = 1,350 + 1,200 + 600 + 500 = 3,650 MVR
```

### Example 2: Package Deal (Relaxation Retreat)

**Package Breakdown:**
```
Room Type: Triple (capacity 3)
Duration: 3 nights
Base Room Price: 2,500 MVR

Included Amenities:
- Full Breakfast Buffet (per_pax_per_night): 350 × 1 × 3 = 1,050 MVR
- Dinner Package (per_pax_per_night): 400 × 1 × 3 = 1,200 MVR
- Spa Access (per_pax): 250 × 1 = 250 MVR
- Gym Access (nightly): 50 × 3 = 150 MVR
- Sauna Access (nightly): 80 × 3 = 240 MVR
- Water Sports (per_pax): 350 × 1 = 350 MVR

Subtotal = 2,500 + 1,050 + 1,200 + 250 + 150 + 240 + 350 = 5,740 MVR
Discount (10%) = 574 MVR
Final Price = 5,166 MVR (1,722 MVR per night)

Savings vs. À la carte = Regular nightly rate (900) × 3 = 2,700 vs. 1,722 = 978 MVR saved
```

---

## Database Migrations

**Files Created (5):**

| Migration | Purpose |
|-----------|---------|
| `2026_04_03_000006_create_accommodation_rooms_table.php` | Rooms with capacity & pricing |
| `2026_04_03_000007_create_room_amenities_table.php` | Amenities with pricing models |
| `2026_04_03_000008_create_room_amenity_table.php` | Room-Amenity many-to-many |
| `2026_04_03_000009_create_accommodation_packages_table.php` | Pre-built packages |
| `2026_04_03_000010_create_package_amenity_table.php` | Package-Amenity many-to-many |

---

## Seeding & Sample Data

**Seeder:** `database/seeders/AccommodationRoomSeeder.php`

**Generated Sample Resort:**
- Property: Paradise Island Resort (North Male Atoll)
- Vendor: resort-vendor@example.com

**Room Types Created:**
```
1. Single Room      - 5 rooms, 300 MVR/night, capacity 1
2. Double Room      - 12 rooms, 450 MVR/night, capacity 2
3. Triple Room      - 8 rooms, 600 MVR/night, capacity 3
4. Suite            - 4 rooms, 900 MVR/night, capacity 4
5. Private Villa    - 3 rooms, 1,500 MVR/night, capacity 4
```

**Amenities Created:** 24 across all categories

**Packages Created:** 3 (Weekend Escape 2-night, Relaxation Retreat 3-night, Ultimate Luxury 4-night)

---

## Frontend Integration

The accommodation system maintains the existing frontend (`customer-category-catalog.blade.php`) while providing robust backend support.

**How Frontend Will Use Backend:**

```blade
@foreach ($catalogProperties as $property)
    <!-- Property card displays: base_price, location, type -->
    
    <!-- On click → /property/{id} or /category-booking/accommodation/{id} -->
    <!-- Detail page shows:
         - Room types with descriptions
         - Available amenities with prices
         - Pre-built packages with savings
         - Total price calculator
    -->
@endforeach
```

---

## API Endpoints (Future Implementation)

**Get Room Details:**
```
GET /api/accommodation/{propertyId}/rooms/{roomId}
Response: Room details + amenities list + packages
```

**Calculate Stay Cost:**
```
POST /api/accommodation/calculate-cost
Body: {
  "room_id": 1,
  "nights": 3,
  "guests": 2,
  "amenity_ids": [5, 11, 23]
}
Response: Full pricing breakdown
```

**Get Package Details:**
```
GET /api/accommodation/packages/{packageId}/pricing
Query: guests=2
Response: Package pricing + included amenities + savings
```

---

## Vendor Workflow

**How Vendors Manage Rooms:**

1. **Create Property** (existing flow)
2. **Add Room Types** - Define single/double/suite/etc.
3. **Configure Amenities** - Create amenities with pricing models
4. **Attach Amenities** - Link amenities to specific rooms
5. **Create Packages** - Bundle rooms + amenities with discounts
6. **Monitor Bookings** - Track reservations & occupancy

---

## Key Differences from Previous System

| Aspect | Before | After |
|--------|--------|-------|
| **Room Data** | Generic property | Individual room types with capacity |
| **Amenities** | Basic property features | Flexible pricing (5 models) |
| **Add-ons** | Hardcoded | Vendor-configured with dynamic pricing |
| **Packages** | None | 3-tier system with auto discounts |
| **Pricing** | Fixed base price | Dynamic based on guests/nights/amenities |
| **Scalability** | Vendor → Properties | Vendor → Properties → Rooms → Amenities → Packages |

---

## Performance Notes

- Room + amenity queries use indexed lookups
- Package pricing calculations are runtime (not stored)
- Seeded resort includes 5 room types, 24 amenities, 3 packages
- All relationships use cascade delete for data integrity

---

## Fields & Constraints

**Capacity Validation:**
- Single: 1 guest max
- Double: 2 guests max
- Triple: 3 guests max
- Suite: 4+ guests
- Villa: Variable (max 4+ handled in front-end)

**Pricing Ranges:**
- Room prices: 300–1,500 MVR/night
- Amenities: 50–600 MVR (depending on type)
- Packages: 800–6,000 MVR total

---

## Future Extensions

1. **Seasonal Pricing** - Dynamic rates per season
2. **Occupancy Tracking** - Real-time room availability
3. **Booking History** - Track which amenities customers prefer
4. **Custom Packages** - Customers build their own bundles
5. **Group Rates** - Bulk discounts for 5+ room bookings
6. **Reviews & Ratings** - Per-room & per-amenity ratings
