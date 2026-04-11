<section id="vendorCategoryWizard" class="ops-section" aria-label="Vendor category request section" style="margin-top:12px;">
    @php
        $requiredDocChecklistByCategory = function_exists('vendorPortalCategoryRequiredDocumentChecklist')
            ? vendorPortalCategoryRequiredDocumentChecklist()
            : [];
    @endphp
    <div class="ops-header">
        <p class="ops-title">Category Subscription / Open / Release Requests</p>
        <span class="ops-chip">Step {{ $vendorOnboardingStep }} of 4</span>
    </div>
    <p class="profile-help">Current selected categories: {{ $selectedCategoryLabels->isNotEmpty() ? $selectedCategoryLabels->join(', ') : 'None selected yet' }}</p>

    <details class="ops-form">
        <summary class="btn btn-secondary" style="cursor:pointer;display:inline-flex;">Request Category Change (with docs)</summary>
        <form class="ops-form" method="POST" action="/portal/vendor/categories/update" enctype="multipart/form-data" style="margin-top:12px;">
            @csrf
            <input type="hidden" name="onboarding_step" value="{{ (int) $vendorOnboardingStep }}">
            <div class="ops-field">
                <label for="category_request_action">Request Type</label>
                <select id="category_request_action" name="request_action" class="ops-select" required>
                    <option value="subscribe" @selected(old('request_action') === 'subscribe')>Subscribe / Add Category</option>
                    <option value="open" @selected(old('request_action') === 'open')>Open Existing Category</option>
                    <option value="release" @selected(old('request_action') === 'release')>Release / Remove Category</option>
                </select>
            </div>
            <div class="ops-field">
                <label>Select categories for this request</label>
                <div class="category-grid">
                    @foreach ($vendorCategoryMap as $categoryKey => $categoryLabel)
                        <label class="category-item" for="category_{{ $categoryKey }}">
                            <input id="category_{{ $categoryKey }}" type="checkbox" name="categories[]" value="{{ $categoryKey }}" @checked(in_array($categoryKey, old('categories', $selectedVendorCategories), true))>
                            <span>{{ $categoryLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="ops-field">
                <label for="request_note">Request Note / Justification</label>
                <textarea id="request_note" name="request_note" class="ops-textarea" maxlength="2000" placeholder="Explain business scope, compliance readiness, and any operational constraints.">{{ old('request_note') }}</textarea>
            </div>
            <div class="ops-field">
                <label for="supporting_documents">Business supporting documents (licenses/certificates)</label>
                <input id="supporting_documents" name="supporting_documents[]" class="ops-input" type="file" accept="application/pdf,image/jpeg,image/png,image/webp" multiple>
                <p class="small" style="margin:6px 0 0;">Upload relevant license, permits, business certificates, or regulatory letters (max 4MB each).</p>
            </div>
            @if (!empty($requiredDocChecklistByCategory))
                <div class="ops-field ops-field-wide">
                    <p class="small" style="margin:0 0 6px;font-weight:700;">Recommended/required checklist by category:</p>
                    <ul class="small" style="margin:0;padding-left:16px;display:grid;gap:4px;">
                        @foreach ($requiredDocChecklistByCategory as $categoryKey => $items)
                            <li>
                                <strong>{{ (string) ($vendorCategoryMap[$categoryKey] ?? $categoryKey) }}:</strong>
                                {{ collect((array) $items)->join('; ') }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <button class="btn btn-primary" type="submit">Submit Category Request</button>
        </form>
    </details>
</section>
