This PR adds automation, hardening, and cleanup for off-site database backups.

Summary:
- Adds automated database dump, gzip, SHA256 checksum, and presigned S3 upload scripts under `automation/`.
- Adds GitHub Actions scheduled workflows to run backups and periodic cleanup.
- Adds `automation/s3_harden.ps1` plus lifecycle/encryption JSON payloads for S3 hardening.
- Adds local & S3 cleanup scripts and a PowerShell deletion report helper.

Why:
- Provide repeatable off-site backups, verify uploads, and enforce retention/encryption.

Testing / Verification steps:
- Run a manual backup and confirm upload + checksum.
- Verify S3 bucket encryption and lifecycle settings.
- Run hosted E2E smoke (optional) to confirm seeding and environment health.
- Confirm GitHub Actions scheduled workflow executes after merge.

Checklist:
- [ ] Confirm CI passes (unit tests / linters)
- [ ] Verify S3 AES256 encryption applied
- [ ] Verify lifecycle policy applied (transition + expiry)
- [ ] Run manual backup and confirm object uploaded + SHA256 match
- [ ] Confirm scheduled workflow triggers next run
- [ ] Merge and monitor first scheduled run
