# Epic #27 — Schedule & Inventory Starter

This starter branch adds migration skeletons for transport tables and a short design doc reference to begin implementation for Epic #27 (Schedule & Inventory).

Contents in this branch:
- `database/migrations/2026_03_04_000001_create_transport_tables.php` — starter migration creating operator, route, schedule, inventory and holds tables.
- `docs/transport-design-doc.md` — design doc (created in main) referenced for implementation details.

Next steps for implementers:
- Implement Eloquent models and basic repositories for `TransportSchedule` and `TransportInventory`.
- Add API endpoints for schedule search and inventory read.
- Implement contract tests (#45) and iterate.

Related: GitHub issue #27
