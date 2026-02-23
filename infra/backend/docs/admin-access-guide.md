# Admin Access Guide (Staff-Friendly)

This guide explains who should use each admin role and what they can do.

## Choose the right role

- **ADMIN_SUPER**
  - Full access to all admin areas.
  - Use for technical leads and trusted platform owners.
- **ADMIN_FINANCE**
  - Handles payments, reconciliation, and billing operations.
  - Can run and manage payment/admin jobs.
- **ADMIN_CARE**
  - Handles vendor care and troubleshooting.
  - Can manage workations/properties and view payment health/status.

> Legacy `ADMIN` currently has full access (same as super admin behavior).

## Quick permissions by job

| Job to do | ADMIN_SUPER / ADMIN | ADMIN_FINANCE | ADMIN_CARE |
|---|---|---|---|
| Check bank/payment health | ✅ | ✅ | ✅ |
| View reconciliation status/history/alerts | ✅ | ✅ | ✅ |
| Run reconciliation now | ✅ | ✅ | ❌ |
| Requeue/cancel/complete payment jobs | ✅ | ✅ | ❌ |
| Prune old completed payment jobs | ✅ | ✅ | ❌ |
| Create/edit/delete workations (property content) | ✅ | ❌ | ✅ |

## Typical team mapping

- **Billing / Accounting / Finance team** → `ADMIN_FINANCE`
- **Vendor care / Vendor troubleshooting team** → `ADMIN_CARE`
- **Platform owner / Super admin** → `ADMIN_SUPER`

## Examples

- If a payment is stuck and needs requeue → **Finance** (`ADMIN_FINANCE`)
- If a vendor reports wrong property details → **Care** (`ADMIN_CARE`)
- If a system-wide incident needs full access → **Super Admin** (`ADMIN_SUPER`)

## Safety tips

- Give the **smallest role needed** for the job.
- Avoid giving full admin access unless truly required.
- Review role assignments regularly.

## Requesting access

Use the manager template: [admin-role-request-template.md](admin-role-request-template.md)

For fast chat/email requests, use the **Quick chat/email version** section in that same template.

For monthly governance checks, run the automated admin review report documented in [../README.md](../README.md) using `npm run admin:review:roles`.
