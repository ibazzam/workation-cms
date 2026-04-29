<section id="vendorDailyCollectionSection" class="card ops-section" aria-label="Vendor daily collection and settlements" data-panel-group="billing">
            <div class="ops-header">
                <p class="ops-title">Billing, Payouts, and Refunds</p>
                <span class="ops-chip">Commission {{ (int) ($commissionRate * 100) }}% + Gateway Fees</span>
            </div>

                <div class="billing-ledger-grid">
                    <article class="billing-ledger-card">
                        <p class="metric-label">Gross Collection</p>
                        <p class="metric-value">MVR {{ number_format($grossCollectionsTotal, 2) }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Total Deductions</p>
                        <p class="metric-value">MVR {{ number_format($totalDeductions ?? ($commissionTotal + $gatewayFeeTotal), 2) }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Net Payout</p>
                        <p class="metric-value">MVR {{ number_format($payoutTotal, 2) }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Settled Invoices</p>
                        <p class="metric-value">{{ $settledInvoicesCount }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Refund Cases</p>
                        <p class="metric-value">{{ $vendorRefundCaseCount }}</p>
                    </article>
                    <article class="billing-ledger-card">
                        <p class="metric-label">Refund Exposure</p>
                        <p class="metric-value">MVR {{ number_format($vendorRefundExposureTotal, 2) }}</p>
                    </article>
                </div>

                <p class="small" style="margin:10px 0 0;">This ledger tracks gross collections, applies total deductions, then shows net payout. Your commission rate and gateway fee policy remain visible at the section header level.</p>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor daily collection table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transactions</th>
                                <th>Gross</th>
                                <th>Total Deductions</th>
                                <th>Payout</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dailyCollection as $day => $daily)
                                <tr>
                                    <td>{{ $day }}</td>
                                    <td>{{ $daily['count'] }}</td>
                                    <td>MVR {{ number_format((float) $daily['gross'], 2) }}</td>
                                    <td>MVR {{ number_format((float) $daily['commission'] + (float) ($daily['gateway_fee'] ?? 0), 2) }}</td>
                                    <td>MVR {{ number_format((float) $daily['payout'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ops-empty">No collection data yet. Add reservations to populate this section.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <div class="ops-table-wrap">
                <table class="ops-table" aria-label="Vendor invoice settlement ledger">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Collected From</th>
                            <th>Status</th>
                            <th>Gross</th>
                            <th>Total Deductions</th>
                            <th>Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($billingLedgerRows->take(20) as $entry)
                            <tr>
                                <td>{{ $entry['invoice_ref'] }}<br>{{ $entry['collection_day'] }}</td>
                                <td>{{ $entry['customer_name'] }}<br>{{ $entry['customer_email'] ?: 'N/A' }}</td>
                                <td>{{ strtoupper($entry['booking_status']) }} / {{ strtoupper($entry['payment_status']) }}<br>{{ $entry['is_settled'] ? 'SETTLED' : 'PENDING' }}</td>
                                <td>{{ $entry['currency'] }} {{ number_format((float) $entry['gross'], 2) }}</td>
                                <td>{{ $entry['currency'] }} {{ number_format((float) $entry['commission'] + (float) ($entry['gateway_fee'] ?? 0), 2) }}</td>
                                <td>{{ $entry['currency'] }} {{ number_format((float) $entry['payout'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="ops-empty">No invoice ledger data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
