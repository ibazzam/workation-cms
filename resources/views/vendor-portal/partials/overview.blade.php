        <section id="vendorSummary" class="summary-grid summary-grid-compact" aria-label="Vendor dashboard summary" data-panel-group="overview">
            <article class="summary-card">
                <p class="summary-label">Listings</p>
                <p id="summaryBookings" class="summary-value">{{ $vendorListingCount }}</p>
                <p class="summary-meta">Active inventory</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Bookings</p>
                <p id="summarySettlements" class="summary-value">{{ $vendorReservations->count() }}</p>
                <p class="summary-meta">Received reservations</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Attention</p>
                <p class="summary-value"><span id="summaryConnectivity" class="status-pill {{ $vendorUnresolvedCareCount > 0 ? 'warn' : 'ok' }}">{{ $vendorUnresolvedCareCount > 0 ? 'OPEN' : 'CLEAR' }}</span></p>
                <p id="summaryLastSync" class="summary-meta">{{ $vendorUnresolvedCareCount }} cases, {{ $vendorPendingReviewResponses }} replies</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Avg Booking</p>
                <p id="summaryToken" class="summary-value">MVR {{ number_format($vendorAverageBookingValue, 2) }}</p>
                <p id="summaryTokenMeta" class="summary-meta">Per reservation</p>
            </article>
        </section>

        <section class="card overview-actions-card" aria-label="Quick actions" data-panel-group="overview">
            <div class="overview-actions-head">
                <p class="label">Quick Actions</p>
                <p class="small">Open the next task directly.</p>
            </div>
            <div class="overview-actions-grid">
                <a class="overview-action" href="/vendor/listings">
                    <span class="overview-action-title">Manage Listings</span>
                    <span class="overview-action-copy">Create, edit, or clean up inventory.</span>
                </a>
                <a class="overview-action" href="/vendor/reservations">
                    <span class="overview-action-title">Review Reservations</span>
                    <span class="overview-action-copy">Check bookings and follow-ups.</span>
                </a>
                <a class="overview-action" href="/vendor/billing">
                    <span class="overview-action-title">View Billing</span>
                    <span class="overview-action-copy">Track settlements and payouts.</span>
                </a>
                <a class="overview-action" href="/vendor/profile">
                    <span class="overview-action-title">Update Profile</span>
                    <span class="overview-action-copy">Keep business details current.</span>
                </a>
            </div>
        </section>
