        <section id="vendorEngagement" class="card ops-section" data-panel-group="engagement" aria-label="Vendor customer engagement tools">
            <div class="ops-header">
                <p class="ops-title">Customer Care</p>
                <span class="ops-chip">Operations</span>
            </div>
            <div class="ops-metrics" style="margin-bottom:10px;">
                <article class="ops-metric">
                    <p class="metric-label">Promotions &amp; Offers</p>
                    <p class="metric-value">{{ $engagementPromotions->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Loyal Customers</p>
                    <p class="metric-value">{{ $engagementLoyalCustomers->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Open Complaints &amp; Messages</p>
                    <p class="metric-value">{{ $engagementInquiries->whereNotIn('status', ['resolved', 'closed', 'replied'])->count() }}</p>
                </article>
                <article class="ops-metric">
                    <p class="metric-label">Reviews Pending Reply</p>
                    <p class="metric-value">{{ $engagementReviews->filter(fn ($row) => trim((string) ($row['response'] ?? '')) === '')->count() }}</p>
                </article>
            </div>

            <div class="ops-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom:10px;">
                <article class="ops-metric" style="text-align:left;">
                    <p class="metric-label">Promotions &amp; Offers</p>
                    <p class="small">Offer execution uses pricing rules and tariff structures. Create promo or demand discount rules, then monitor uptake.</p>
                    <p style="margin-top:8px;"><a class="btn btn-secondary" href="/vendor/pricing">Open Pricing &amp; Tariffs</a></p>
                </article>
                <article class="ops-metric" style="text-align:left;">
                    <p class="metric-label">Complaint Handling &amp; Replies</p>
                    <p class="small">Use inquiries and review responses to communicate back and forth with customers, resolve complaints, and protect listing reputation.</p>
                    <p style="margin-top:8px;"><a class="btn btn-secondary" href="/vendor/promotions">Open Customer Care Queue</a></p>
                </article>
            </div>

            <div class="ops-table-wrap" style="margin-bottom:10px;">
                <p class="label">Active Promotion Rules</p>
                <table class="ops-table" aria-label="Promotion rules table">
                    <thead>
                        <tr>
                            <th>Rule</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Window</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($engagementPromotions->take(10) as $promotion)
                            <tr>
                                <td>{{ (string) ($promotion['name'] ?? 'Promotion') }}</td>
                                <td>{{ strtoupper(str_replace('_', ' ', (string) ($promotion['rule_type'] ?? 'promo_discount'))) }}</td>
                                <td>{{ number_format((float) ($promotion['value'] ?? 0), 2) }}</td>
                                <td>{{ (string) (($promotion['starts_on'] ?? '') ?: '-') }} - {{ (string) (($promotion['ends_on'] ?? '') ?: '-') }}</td>
                                <td>{{ (bool) ($promotion['is_active'] ?? false) ? 'ACTIVE' : 'INACTIVE' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="ops-empty">No promotion rules yet. Create pricing rules using Promo Discount or Demand Discount.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ops-table-wrap" style="margin-bottom:10px;">
                <p class="label">Repeat Customers (Loyalty Signals)</p>
                <table class="ops-table" aria-label="Loyal customer signals table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Reservations</th>
                            <th>Total Spend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($engagementLoyalCustomers->take(10) as $customer)
                            <tr>
                                <td>{{ (string) ($customer['customer_name'] ?? 'Returning Guest') }}</td>
                                <td>{{ (string) ($customer['customer_email'] ?? '-') }}</td>
                                <td>{{ (int) ($customer['reservations_count'] ?? 0) }}</td>
                                <td>MVR {{ number_format((float) ($customer['total_spend'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="ops-empty">No repeat-customer pattern detected yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($engagementLoyaltyTable !== '' && $engagementLoyaltyPrograms->isNotEmpty())
                    <p class="small" style="margin-top:8px;">Detected loyalty source: {{ $engagementLoyaltyTable }}</p>
                @endif
            </div>

            <div class="ops-table-wrap" style="margin-bottom:10px;">
                <p class="label">Customer Inquiries</p>
                @if ($engagementInquiriesTable === '')
                    <p class="small">No inquiry table detected. Supported tables: vendor_customer_inquiries, vendor_inquiries, customer_inquiries, vendor_messages.</p>
                @endif
                <table class="ops-table" aria-label="Customer inquiries table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Subject / Message</th>
                            <th>Status</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($engagementInquiries->take(12) as $inquiry)
                            <tr>
                                <td>{{ (string) ($inquiry['customer_name'] ?? 'Guest') }}<br>{{ (string) ($inquiry['customer_email'] ?? '') }}</td>
                                <td><strong>{{ (string) (($inquiry['subject'] ?? '') ?: 'General inquiry') }}</strong><br>{{ (string) (($inquiry['message'] ?? '') ?: '-') }}</td>
                                <td>{{ strtoupper((string) ($inquiry['status'] ?? 'open')) }}</td>
                                <td>
                                    @if ($engagementInquiriesTable !== '' && (int) ($inquiry['id'] ?? 0) > 0)
                                        <form method="POST" action="/portal/vendor/inquiries/{{ (int) ($inquiry['id'] ?? 0) }}/status">
                                            @csrf
                                            <input type="hidden" name="table" value="{{ $engagementInquiriesTable }}">
                                            <select class="ops-select" name="status" style="margin-bottom:6px;">
                                                <option value="open" @selected(($inquiry['status'] ?? '') === 'open')>Open</option>
                                                <option value="pending" @selected(($inquiry['status'] ?? '') === 'pending')>Pending</option>
                                                <option value="in_progress" @selected(($inquiry['status'] ?? '') === 'in_progress')>In Progress</option>
                                                <option value="replied" @selected(($inquiry['status'] ?? '') === 'replied')>Replied</option>
                                                <option value="resolved" @selected(($inquiry['status'] ?? '') === 'resolved')>Resolved</option>
                                                <option value="closed" @selected(($inquiry['status'] ?? '') === 'closed')>Closed</option>
                                            </select>
                                            <textarea class="ops-textarea" name="response" rows="2" maxlength="3000" placeholder="Write response...">{{ (string) ($inquiry['response'] ?? '') }}</textarea>
                                            <button class="btn btn-secondary" type="submit" style="margin-top:6px;">Save</button>
                                        </form>
                                    @else
                                        <span class="small">{{ (string) (($inquiry['response'] ?? '') ?: 'No response yet') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="ops-empty">No customer inquiries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ops-table-wrap">
                <p class="label">Customer Reviews</p>
                @if ($engagementReviewsTable === '')
                    <p class="small">No review table detected. Supported tables: vendor_property_reviews, vendor_reviews, customer_reviews, property_reviews.</p>
                @endif
                <table class="ops-table" aria-label="Customer reviews table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Rating / Review</th>
                            <th>Status</th>
                            <th>Vendor Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($engagementReviews->take(12) as $review)
                            <tr>
                                <td>{{ (string) ($review['customer_name'] ?? 'Guest') }}<br>{{ (string) ($review['customer_email'] ?? '') }}</td>
                                <td>{{ number_format((float) ($review['rating'] ?? 0), 1) }}/5<br>{{ (string) (($review['comment'] ?? '') ?: '-') }}</td>
                                <td>{{ strtoupper((string) ($review['status'] ?? 'pending')) }}</td>
                                <td>
                                    @if ($engagementReviewsTable !== '' && (int) ($review['id'] ?? 0) > 0)
                                        <form method="POST" action="/portal/vendor/reviews/{{ (int) ($review['id'] ?? 0) }}/respond">
                                            @csrf
                                            <input type="hidden" name="table" value="{{ $engagementReviewsTable }}">
                                            <select class="ops-select" name="status" style="margin-bottom:6px;">
                                                <option value="pending" @selected(($review['status'] ?? '') === 'pending')>Pending</option>
                                                <option value="approved" @selected(($review['status'] ?? '') === 'approved')>Approved</option>
                                                <option value="published" @selected(($review['status'] ?? '') === 'published')>Published</option>
                                                <option value="responded" @selected(($review['status'] ?? '') === 'responded')>Responded</option>
                                                <option value="hidden" @selected(($review['status'] ?? '') === 'hidden')>Hidden</option>
                                                <option value="rejected" @selected(($review['status'] ?? '') === 'rejected')>Rejected</option>
                                            </select>
                                            <textarea class="ops-textarea" name="response" rows="2" maxlength="3000" placeholder="Reply to customer review...">{{ (string) ($review['response'] ?? '') }}</textarea>
                                            <button class="btn btn-secondary" type="submit" style="margin-top:6px;">Save</button>
                                        </form>
                                    @else
                                        <span class="small">{{ (string) (($review['response'] ?? '') ?: 'No response yet') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="ops-empty">No customer reviews found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
