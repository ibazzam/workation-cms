# Vendor Channel Manager Portal Blueprint

## Purpose
Define how vendors manage OTA channels (Booking.com, Agoda, Airbnb, etc.) from the same vendor portal while centralizing reservations and inventory in Workation.

## Product Decision
- Use the same vendor portal login and navigation.
- Add a dedicated module: `Distribution`.
- Keep daily booking and inventory operations in existing `Reservations` and `Calendar` areas.
- Keep setup and connectivity actions inside `Distribution`.

## Hard Invariant (Must Hold)
- If a room is sold on any external OTA, the same room/date inventory in Workation must be reduced atomically and reflected as sold out when sellable inventory reaches zero.
- Workation and channels must never diverge silently. Any failed outbound update must create a visible sync incident.

Sellable inventory formula:
- `sellable = physical_rooms - sold_rooms - hold_rooms - safety_buffer`
- If `sellable <= 0`, mark `closed_out = true` and propagate stop-sell to all connected channels.

## Information Architecture (Vendor Side)
Top-level nav additions:
- Dashboard
- Listings
- Rates and Inventory
- Reservations
- Distribution (new)
- Reports
- Settings

Distribution submenu:
- Channels
- Room Mapping
- Rate Plan Mapping
- Sync Rules
- Sync Health
- Audit Log

## Page-Level UX and Flow

### 1) Distribution > Channels
Goal: connect and authorize OTA/channel manager accounts.

UI sections:
- Connected channels cards (status, account name, last sync, actions)
- Available channels gallery (Connect button)
- Credential/authorization modal

Key actions:
- Connect channel account
- Reauthorize token
- Pause channel sync
- Disconnect channel

Status values:
- Connected
- Action required
- Paused
- Disconnected

### 2) Distribution > Room Mapping
Goal: map OTA room entities to Workation room entities.

UI sections:
- Channel selector
- Unmapped external rooms panel
- Workation room types panel
- Mapping table with confidence indicators

Key actions:
- Create mapping
- Edit mapping
- Bulk auto-map (name match + capacity check)
- Validate mapping

Hard validation rules:
- One external room maps to exactly one internal room
- Capacity mismatch requires explicit confirmation
- Inactive internal rooms cannot be mapped

### 3) Distribution > Rate Plan Mapping
Goal: map OTA rate plans and occupancy rules to internal pricing plans.

UI sections:
- External rate plan list
- Internal plan target
- Occupancy/tax policy preview

Key actions:
- Map rate plan
- Set fallback policy (stop-sell vs keep-last-rate)
- Set tax inclusion mode per channel

### 4) Distribution > Sync Rules
Goal: define system behavior for ARI and reservation processing.

Rules:
- Inventory source: Workation master inventory only
- Rate source: Workation rate engine or mapped fixed plan
- Safety buffer per room/date (e.g. keep 1 unit closed)
- Stop-sell on mapping error
- Auto-close on payment risk flag (optional)

Controls:
- Toggle real-time push
- Toggle scheduled reconciliation
- Override per channel

### 5) Distribution > Sync Health
Goal: operational monitor for failures and latency.

Widgets:
- Last successful push per channel
- Failed pushes (24h)
- Webhook failures (24h)
- Inbound reservation delay
- Queue depth

Tables:
- Failed events with reason and retry count
- Dead-letter queue items

Actions:
- Retry selected
- Retry all recoverable
- Export incident CSV

### 6) Distribution > Audit Log
Goal: immutable history for support and compliance.

Captured events:
- Connection and token events
- Mapping changes
- Rule changes
- Manual retries and overrides
- Reservation source events (new/modify/cancel)

## Unified Daily Operations (Existing Areas)

### Reservations screen additions
Add fields:
- `source_channel` (Direct, Booking, Agoda, Airbnb, etc.)
- `external_booking_id`
- `channel_status` (confirmed, modified, cancelled, pending_ack)
- `sync_state` (in_sync, retrying, failed)

Add filters:
- By channel
- By sync state
- By external booking id

### Rates and Inventory calendar additions
Add overlays:
- Channel sync indicator per date cell
- Effective sellable inventory after buffer
- Warning icon if channel drift detected

## Backend Processing Model

### Inbound reservation pipeline
1. Receive webhook/event
2. Verify signature and source
3. Idempotency check by `channel + external_booking_id + event_version`
4. Lock inventory rows for affected dates (`SELECT ... FOR UPDATE`)
5. Reserve inventory (or release on cancel/modify) in one transaction
6. Create or update reservation
7. Commit transaction only if all row updates succeed
8. Emit outbound ARI updates to other channels immediately after commit
9. Persist processing result and metrics

### Outbound ARI pipeline
1. Gather changed inventory/rate/restriction deltas
2. Transform to channel payload
3. Push with retry/backoff
4. Mark delivery status
5. Raise alert after retry exhaustion

### Reconciliation job
- Frequency: every 15-30 minutes
- Compare channel snapshot vs internal snapshot
- Auto-heal minor drift
- Raise incident for hard conflicts

## Data Model Additions (Suggested)
- `channel_accounts`
  - vendor_id, channel_code, account_ref, auth_status, token_meta, connected_at
- `channel_room_mappings`
  - channel_account_id, external_room_id, internal_room_id, mapping_status
- `channel_rate_plan_mappings`
  - channel_account_id, external_rate_id, internal_plan_id, tax_mode, occupancy_mode
- `channel_sync_rules`
  - vendor_id, channel_code, safety_buffer, stop_sell_on_error, realtime_enabled
- `channel_events`
  - direction, channel_code, event_type, payload_hash, external_id, status, retry_count
- `inventory_ledger`
  - room_id, date, physical, sold, hold, buffer, sellable, version
- `reservation_channel_links`
  - reservation_id, channel_code, external_booking_id, external_version

## Permission Model (Vendor Roles)

### Vendor Owner
- Full access to all Distribution pages
- Can connect/disconnect channels
- Can change sync rules

### Revenue Manager
- Access to mappings, rates, inventory sync controls
- Cannot disconnect accounts

### Reservation Agent
- Read-only access to Distribution
- Full access to Reservations
- Can trigger retry on failed reservation import

### Finance
- Read-only on channel source and payout-related references
- No mapping or sync rule changes

## Guardrails
- Do not allow go-live unless room and rate mapping coverage is 100% for active rooms.
- Enforce idempotency and optimistic locking for all reservation mutations.
- Block direct inventory edit if a channel sync transaction is in progress for same room/date range.
- Keep a dead-letter queue with replay tools.
- Fail closed on insufficient inventory: reject late inbound event and raise incident instead of overselling.
- Reject duplicate inbound events by idempotency key; replay should be no-op.

## Rollout Plan

Phase 1 (2-4 weeks):
- Channels page
- Room mapping
- Inbound reservation ingest
- Reservation source visibility

Phase 2 (2-4 weeks):
- Outbound inventory sync
- Sync health and retries
- Reconciliation job

Phase 3 (3-6 weeks):
- Rate/restriction sync
- Full audit trail
- SLA alerts and operational dashboards

## Success Metrics
- Reservation ingestion success rate >= 99.9%
- Inventory drift rate < 0.1%
- Mean time to recover failed sync < 15 minutes
- Overbooking incidents = 0 attributable to sync race conditions

## Recommended First Build in This Repo
- Add `Distribution` menu and shell pages in vendor portal
- Implement `channel_accounts` + `channel_room_mappings` migrations
- Add webhook endpoint + signature verification middleware
- Add reservation idempotency service and inventory lock service
- Add Sync Health dashboard backed by `channel_events`
