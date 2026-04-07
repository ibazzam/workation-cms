        <section id="vendorSummary" class="summary-grid" aria-label="Vendor dashboard summary" data-panel-group="overview">
            <article class="summary-card">
                <p class="summary-label">Total Listings</p>
                <p id="summaryBookings" class="summary-value">{{ $vendorListingCount }}</p>
                <p class="summary-meta">Properties and services currently managed in your vendor account</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Reservations</p>
                <p id="summarySettlements" class="summary-value">{{ $vendorReservations->count() }}</p>
                <p class="summary-meta">All reservation records received across your listings</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Average Booking Value</p>
                <p id="summaryToken" class="summary-value">MVR {{ number_format($vendorAverageBookingValue, 2) }}</p>
                <p id="summaryTokenMeta" class="summary-meta">Average gross value per reservation</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Customer Care Queue</p>
                <p class="summary-value"><span id="summaryConnectivity" class="status-pill {{ $vendorUnresolvedCareCount > 0 ? 'warn' : 'ok' }}">{{ $vendorUnresolvedCareCount > 0 ? 'ACTION NEEDED' : 'ON TRACK' }}</span></p>
                <p id="summaryLastSync" class="summary-meta">{{ $vendorUnresolvedCareCount }} open conversations and {{ $vendorPendingReviewResponses }} pending review replies</p>
            </article>
        </section>

        <div id="vendorSummaryActions" class="summary-actions" data-panel-group="overview">
            <button id="refreshSummary" type="button" class="summary-refresh">Refresh API Snapshot</button>
        </div>

        <section id="vendorProgressSnapshot" class="card progress-snapshot" aria-label="Vendor activity progress snapshot" data-panel-group="overview">
            <div class="ops-header">
                <p class="ops-title">Vendor Progress Snapshot</p>
                <span class="ops-chip">Live from your account data</span>
            </div>
            <div class="progress-grid">
                <article class="progress-card">
                    <p class="progress-label">Pending Reservations</p>
                    <p class="progress-value">{{ $vendorPendingReservationsCount }}</p>
                    <p class="progress-meta">Reservations waiting for action or confirmation</p>
                </article>
                <article class="progress-card">
                    <p class="progress-label">Revenue Collected</p>
                    <p class="progress-value">MVR {{ number_format($grossCollectionsTotal, 2) }}</p>
                    <p class="progress-meta">Gross collections before Workation commission deductions</p>
                </article>
                <article class="progress-card">
                    <p class="progress-label">Expected Payout</p>
                    <p class="progress-value">MVR {{ number_format($expectedPayoutTotal, 2) }}</p>
                    <p class="progress-meta">Pending payout expected from Workation</p>
                </article>
                <article class="progress-card">
                    <p class="progress-label">Completed Stays</p>
                    <p class="progress-value">{{ $vendorCompletedReservationsCount }}</p>
                    <p class="progress-meta">Completed reservations contributing to payout confidence</p>
                </article>
            </div>
        </section>

        <section id="vendorReportsSection" class="card ops-section" aria-label="Vendor reports and performance" data-panel-group="overview">
            <div class="ops-header">
                <p class="ops-title">Reports &amp; Performance</p>
                <span class="ops-chip">Home dashboard intelligence</span>
                <a class="btn btn-secondary" href="/vendor/reports/export" style="margin-left:auto;">Download CSV</a>
            </div>
            <div class="reports-grid">
                <article class="report-card">
                    <h3>Sales Performance</h3>
                    <p>{{ $vendorConfirmedReservationsCount }} confirmed reservations, {{ $vendorCompletedReservationsCount }} completed stays, and an average booking value of MVR {{ number_format($vendorAverageBookingValue, 2) }}.</p>
                </article>
                <article class="report-card">
                    <h3>Payout Forecast</h3>
                    <p>MVR {{ number_format($settledPayoutTotal, 2) }} settled and MVR {{ number_format($expectedPayoutTotal, 2) }} still expected from Workation settlements.</p>
                </article>
                <article class="report-card">
                    <h3>Care &amp; Reputation</h3>
                    <p>{{ $vendorUnresolvedCareCount }} unresolved care cases and {{ $vendorPendingReviewResponses }} reviews still waiting for a vendor response.</p>
                </article>
            </div>
        </section>

        <section id="payoutCenter" class="card payout-center" aria-label="Vendor payout center" data-panel-group="overview">
            <p class="label">Payout Center</p>
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
