        <section id="vendorSummary" class="summary-grid" aria-label="Vendor dashboard summary" data-panel-group="overview">
            <article class="summary-card">
                <p class="summary-label">Total Listings</p>
                <p id="summaryBookings" class="summary-value">{{ $vendorListingCount }}</p>
                <p class="summary-meta">Active inventory in your workspace</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Reservations</p>
                <p id="summarySettlements" class="summary-value">{{ $vendorReservations->count() }}</p>
                <p class="summary-meta">Total booking records received</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Avg Booking</p>
                <p id="summaryToken" class="summary-value">MVR {{ number_format($vendorAverageBookingValue, 2) }}</p>
                <p id="summaryTokenMeta" class="summary-meta">Average gross per reservation</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Care Queue</p>
                <p class="summary-value"><span id="summaryConnectivity" class="status-pill {{ $vendorUnresolvedCareCount > 0 ? 'warn' : 'ok' }}">{{ $vendorUnresolvedCareCount > 0 ? 'ACTION NEEDED' : 'ON TRACK' }}</span></p>
                <p id="summaryLastSync" class="summary-meta">{{ $vendorUnresolvedCareCount }} open cases, {{ $vendorPendingReviewResponses }} pending replies</p>
            </article>
        </section>

        <section id="payoutCenter" class="card payout-center" aria-label="Vendor payout center" data-panel-group="overview">
            <p class="label">Payouts</p>
            <div class="payout-grid">
                <article class="payout-metric">
                    <p class="metric-label">Settled Total</p>
                    <p id="payoutSettledTotal" class="metric-value">MVR 0.00</p>
                </article>
                <article class="payout-metric">
                    <p class="metric-label">Pending Total</p>
                    <p id="payoutPendingTotal" class="metric-value">MVR 0.00</p>
                </article>
                <article class="payout-metric">
                    <p class="metric-label">Next Payout Estimate</p>
                    <p id="payoutNextEstimate" class="metric-value">N/A</p>
                </article>
            </div>
            <div class="payout-table-wrap">
                <table class="payout-table" aria-label="Recent payouts">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="payoutRows">
                        <tr>
                            <td colspan="4" class="payout-empty">Refresh summary to load payout data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
