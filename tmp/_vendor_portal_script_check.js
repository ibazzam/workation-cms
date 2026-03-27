(function () {
            const root = document.querySelector(".page");
            const apiBase = root ? root.getAttribute("data-api-base") : "";
            const tokenInput = document.getElementById("tokenInput");
            const tokenState = document.getElementById("tokenState");
            const tokenMeta = document.getElementById("tokenMeta");
            const output = document.getElementById("output");
            const summaryBookings = document.getElementById("summaryBookings");
            const summarySettlements = document.getElementById("summarySettlements");
            const summaryToken = document.getElementById("summaryToken");
            const summaryTokenMeta = document.getElementById("summaryTokenMeta");
            const summaryConnectivity = document.getElementById("summaryConnectivity");
            const summaryLastSync = document.getElementById("summaryLastSync");
            const refreshSummaryBtn = document.getElementById("refreshSummary");
            const payoutSettledTotal = document.getElementById("payoutSettledTotal");
            const payoutPendingTotal = document.getElementById("payoutPendingTotal");
            const payoutNextEstimate = document.getElementById("payoutNextEstimate");
            const payoutRows = document.getElementById("payoutRows");
            const navLinks = Array.from(document.querySelectorAll('.portal-nav a[data-panel-key]'));
            const panelGroups = Array.from(document.querySelectorAll('[data-panel-group]'));
            const listingStepPanels = Array.from(document.querySelectorAll('[data-listing-step]'));
            const validPanelKeys = new Set(navLinks.map((link) => String(link.dataset.panelKey || "")).filter(Boolean));
            const locationCountry = document.getElementById("location_country");
            const locationState = document.getElementById("location_state");
            const locationCity = document.getElementById("location_city");
            const mapLatitude = document.getElementById("map_latitude");
            const mapLongitude = document.getElementById("map_longitude");
            const mapPlaceId = document.getElementById("map_place_id");
            const billingCountry = document.getElementById("billing_country");
            const billingState = document.getElementById("billing_state");
            const billingCity = document.getElementById("billing_city");
            const openPropertyCreateForm = document.getElementById("openPropertyCreateForm");
            const closePropertyCreateForm = document.getElementById("closePropertyCreateForm");
            const propertyCreateForm = document.getElementById("propertyCreateForm");
            const propertyCreateFormTitle = document.getElementById("propertyCreateFormTitle");
            const propertyCreateFormSubtitle = document.getElementById("propertyCreateFormSubtitle");
            const propertyCreateSubmitButton = document.getElementById("propertyCreateSubmitButton");
            const propertyCategorySelect = document.getElementById("property_listing_category");
            const propertyTypeSelect = document.getElementById("property_type");
            const propertyCategoryScopeNote = document.getElementById("propertyCategoryScopeNote");
            const categoryScopedFields = Array.from(document.querySelectorAll("[data-category-scope]"));
            const categoryViewPanels = Array.from(document.querySelectorAll('[data-category-view]'));
            const openRoomCreateForm = document.getElementById("openRoomCreateForm");
            const closeRoomCreateForm = document.getElementById("closeRoomCreateForm");
            const roomCreateForm = document.getElementById("roomCreateForm");
            const roomPropertySelect = document.getElementById("room_vendor_property_id");
            const roomQuickOpenButtons = Array.from(document.querySelectorAll("[data-open-room-form]"));
            const propertyEditButtons = Array.from(document.querySelectorAll('[data-open-property-edit]'));
            const propertyEditCancelButtons = Array.from(document.querySelectorAll('[data-close-property-edit]'));
            const roomEditButtons = Array.from(document.querySelectorAll('[data-open-room-edit]'));
            const roomEditCancelButtons = Array.from(document.querySelectorAll('[data-close-room-edit]'));
            const listingCategoryShortcutButtons = Array.from(document.querySelectorAll('[data-listing-category-shortcut]'));
            const propertyListingRows = Array.from(document.querySelectorAll('[data-property-row]'));
            const guidedTrackProperty = document.getElementById("guidedTrackProperty");
            const guidedWizardSteps = document.getElementById("guidedWizardSteps");
            const guidedWizardStepText = document.getElementById("guidedWizardStepText");
            const guidedWizardProgressFill = document.getElementById("guidedWizardProgressFill");
            const guidedWizardPrev = document.getElementById("guidedWizardPrev");
            const guidedWizardResume = document.getElementById("guidedWizardResume");
            const guidedWizardNext = document.getElementById("guidedWizardNext");
            const serverPanelKey = "x";
            const listingWizardStep = Number("x") || 1;
            let listingWizardStarted = serverPanelKey === "listings";
            let listingWizardPanelStep = 1;
            let guidedWizardTrack = "property";
            let guidedWizardIndex = 0;
            const vendorPropertiesCount = Number("x") || 0;
            const vendorRoomsCount = Number("x") || 0;
            const vendorBillingReady = "x" === "1";
            const GUIDED_WIZARD_STORAGE_KEY = "workation_vendor_guided_wizard";

            const guidedWizardFlows = {
                property: [
                    {
                        title: "Property setup",
                        hint: "Choose category and set listing basics.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 1,
                        openPropertyForm: true,
                    },
                    {
                        title: "Review and refine",
                        hint: "Confirm created property and update details.",
                        panel: "listings",
                        targetId: "vendorPropertiesSection",
                        wizardStep: 2,
                    },
                    {
                        title: "Room inventory",
                        hint: "Add room types and occupancy for each property.",
                        panel: "listings",
                        targetId: "vendorRoomsSection",
                        wizardStep: 3,
                        openRoomForm: true,
                    },
                    {
                        title: "Photos and media",
                        hint: "Upload property and room photos.",
                        panel: "listings",
                        targetId: "vendorMediaSection",
                        wizardStep: 4,
                    },
                    {
                        title: "Publish readiness",
                        hint: "Check pricing, availability, and billing before go-live.",
                        panel: "reservations",
                        targetId: "vendorPricingSection",
                    },
                ],
            };

            const SESSION_KEY = "workation_vendor_token";

            function setState(type, text) {
                tokenState.className = "state " + type;
                tokenState.textContent = text;
            }

            function setMeta(text) {
                if (tokenMeta) {
                    tokenMeta.textContent = text;
                }
            }

            function getToken() {
                return sessionStorage.getItem(SESSION_KEY) || "";
            }

            function decodeBase64Url(value) {
                try {
                    const normalized = value.replace(/-/g, "+").replace(/_/g, "/");
                    const padded = normalized + "=".repeat((4 - (normalized.length % 4)) % 4);
                    return atob(padded);
                } catch (error) {
                    return "";
                }
            }

            function parseJwtPayload(token) {
                const parts = String(token || "").split(".");
                if (parts.length !== 3) {
                    return null;
                }
                const payloadRaw = decodeBase64Url(parts[1]);
                if (!payloadRaw) {
                    return null;
                }
                try {
                    return JSON.parse(payloadRaw);
                } catch (error) {
                    return null;
                }
            }

            function formatDuration(seconds) {
                const total = Math.max(0, Math.floor(seconds));
                const hours = Math.floor(total / 3600);
                const minutes = Math.floor((total % 3600) / 60);
                if (hours > 0) {
                    return hours + "h " + minutes + "m";
                }
                return minutes + "m";
            }

            function formatDateTime(epochSeconds) {
                return new Date(epochSeconds * 1000).toLocaleString();
            }

            function evaluateToken(token) {
                const payload = parseJwtPayload(token);
                if (!payload) {
                    return {
                        isValidFormat: false,
                        isUsable: false,
                        stateType: "err",
                        stateText: "INVALID TOKEN FORMAT",
                        metaText: "Expected a JWT with 3 parts: header.payload.signature"
                    };
                }

                const exp = Number(payload.exp);
                if (!Number.isFinite(exp)) {
                    return {
                        isValidFormat: true,
                        isUsable: true,
                        stateType: "warn",
                        stateText: "TOKEN SAVED (NO EXP)",
                        metaText: "No expiration claim found. Token expiry cannot be predicted."
                    };
                }

                const now = Math.floor(Date.now() / 1000);
                const secondsLeft = exp - now;
                const expiresAt = formatDateTime(exp);
                if (secondsLeft <= 0) {
                    return {
                        isValidFormat: true,
                        isUsable: false,
                        stateType: "err",
                        stateText: "TOKEN EXPIRED",
                        metaText: "Expired at " + expiresAt + ". Save a fresh token."
                    };
                }

                if (secondsLeft <= 5 * 60) {
                    return {
                        isValidFormat: true,
                        isUsable: true,
                        stateType: "warn",
                        stateText: "TOKEN EXPIRING SOON",
                        metaText: "Expires in " + formatDuration(secondsLeft) + " (" + expiresAt + ")"
                    };
                }

                return {
                    isValidFormat: true,
                    isUsable: true,
                    stateType: "ok",
                    stateText: "TOKEN READY",
                    metaText: "Expires in " + formatDuration(secondsLeft) + " (" + expiresAt + ")"
                };
            }

            function applyTokenFeedback(token, fallbackType, fallbackStateText, fallbackMetaText) {
                if (!token) {
                    setState(fallbackType || "warn", fallbackStateText || "TOKEN NOT SET");
                    setMeta(fallbackMetaText || "Token is stored only in this browser tab session.");
                    return;
                }

                const verdict = evaluateToken(token);
                setState(verdict.stateType, verdict.stateText);
                setMeta(verdict.metaText);
            }

            function saveToken() {
                const value = (tokenInput.value || "").trim();
                if (!value) {
                    setState("warn", "TOKEN NOT SET");
                    setMeta("Paste a JWT token to continue.");
                    return;
                }

                const verdict = evaluateToken(value);
                if (!verdict.isValidFormat || !verdict.isUsable) {
                    setState(verdict.stateType, verdict.stateText);
                    setMeta(verdict.metaText);
                    return;
                }

                sessionStorage.setItem(SESSION_KEY, value);
                tokenInput.value = "";
                applyTokenFeedback(value, "ok", "TOKEN SAVED");
                refreshSummary();
            }

            function clearToken() {
                sessionStorage.removeItem(SESSION_KEY);
                tokenInput.value = "";
                setState("warn", "TOKEN CLEARED");
                setMeta("Token removed from this tab session.");
                setSummaryDefaults();
            }

            function setSummaryDefaults() {
                if (summaryBookings) summaryBookings.textContent = "-";
                if (summarySettlements) summarySettlements.textContent = "-";
                if (summaryToken) summaryToken.textContent = "N/A";
                if (summaryTokenMeta) summaryTokenMeta.textContent = "Save token to evaluate readiness";
                if (summaryConnectivity) {
                    summaryConnectivity.className = "status-pill warn";
                    summaryConnectivity.textContent = "UNKNOWN";
                }
                if (summaryLastSync) {
                    summaryLastSync.textContent = "Last sync: not run yet";
                }

                if (payoutSettledTotal) payoutSettledTotal.textContent = "MVR 0.00";
                if (payoutPendingTotal) payoutPendingTotal.textContent = "MVR 0.00";
                if (payoutNextEstimate) payoutNextEstimate.textContent = "N/A";
                if (payoutRows) {
                    payoutRows.innerHTML = '<tr><td colspan="4" class="payout-empty">Refresh summary to load payout data.</td></tr>';
                }
            }

            function formatCurrency(value) {
                const amount = Number(value);
                if (!Number.isFinite(amount)) {
                    return "MVR 0.00";
                }
                return "MVR " + amount.toFixed(2);
            }

            function normalizeSettlementRows(payload) {
                if (Array.isArray(payload)) return payload;
                if (payload && Array.isArray(payload.data)) return payload.data;
                if (payload && Array.isArray(payload.items)) return payload.items;
                return [];
            }

            function extractAmount(row) {
                const candidates = [row && row.amount, row && row.net_amount, row && row.total, row && row.value];
                for (const value of candidates) {
                    const parsed = Number(value);
                    if (Number.isFinite(parsed)) {
                        return parsed;
                    }
                }
                return 0;
            }

            function toRowStatus(row) {
                const raw = String((row && (row.status || row.state)) || "").trim();
                return raw === "" ? "UNKNOWN" : raw.toUpperCase();
            }

            function toRowReference(row, index) {
                return String((row && (row.reference || row.settlement_id || row.id || row.code)) || "SETTLEMENT-" + (index + 1));
            }

            function toRowDate(row) {
                const raw = String((row && (row.paid_at || row.created_at || row.date)) || "").trim();
                if (!raw) return "N/A";
                const date = new Date(raw);
                if (Number.isNaN(date.getTime())) return raw;
                return date.toLocaleDateString();
            }

            function renderPayoutCenter(payload) {
                const rows = normalizeSettlementRows(payload);
                let settledTotal = 0;
                let pendingTotal = 0;

                rows.forEach((row) => {
                    const amount = extractAmount(row);
                    const status = toRowStatus(row);
                    if (status.includes("SETTLED") || status.includes("PAID") || status.includes("COMPLETED")) {
                        settledTotal += amount;
                    } else {
                        pendingTotal += amount;
                    }
                });

                if (payoutSettledTotal) payoutSettledTotal.textContent = formatCurrency(settledTotal);
                if (payoutPendingTotal) payoutPendingTotal.textContent = formatCurrency(pendingTotal);

                const nextEstimateDate = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000);
                if (payoutNextEstimate) {
                    payoutNextEstimate.textContent = rows.length === 0
                        ? "N/A"
                        : nextEstimateDate.toLocaleDateString();
                }

                if (!payoutRows) return;
                if (rows.length === 0) {
                    payoutRows.innerHTML = '<tr><td colspan="4" class="payout-empty">No settlements returned for this token yet.</td></tr>';
                    return;
                }

                payoutRows.innerHTML = rows.slice(0, 8).map((row, index) => {
                    const reference = toRowReference(row, index);
                    const status = toRowStatus(row);
                    const amount = formatCurrency(extractAmount(row));
                    const date = toRowDate(row);
                    return '<tr><td>' + reference + '</td><td>' + status + '</td><td>' + amount + '</td><td>' + date + '</td></tr>';
                }).join('');
            }

            async function fetchJsonWithAuth(path, token) {
                const res = await fetch(apiBase + path, {
                    method: "GET",
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Accept": "application/json"
                    },
                    cache: "no-store"
                });

                const bodyText = await res.text();
                let json = null;
                try {
                    json = JSON.parse(bodyText);
                } catch (error) {
                    json = null;
                }

                return { ok: res.ok, status: res.status, json: json, text: bodyText };
            }

            function deriveCount(payload) {
                if (Array.isArray(payload)) {
                    return payload.length;
                }
                if (payload && Array.isArray(payload.data)) {
                    return payload.data.length;
                }
                if (payload && Array.isArray(payload.items)) {
                    return payload.items.length;
                }
                if (payload && Number.isFinite(Number(payload.total))) {
                    return Number(payload.total);
                }
                return null;
            }

            function setConnectivity(type, label, lastSyncText) {
                if (summaryConnectivity) {
                    summaryConnectivity.className = "status-pill " + type;
                    summaryConnectivity.textContent = label;
                }
                if (summaryLastSync) {
                    summaryLastSync.textContent = "Last sync: " + lastSyncText;
                }
            }

            function setActiveNavLink(panelKey) {
                navLinks.forEach((link) => {
                    const isActive = (link.dataset.panelKey || "") === panelKey;
                    link.classList.toggle("is-active", isActive);
                });
            }

            function showPanelGroup(panelKey) {
                panelGroups.forEach((panel) => {
                    panel.hidden = (panel.getAttribute("data-panel-group") || "") !== panelKey;
                });
                setActiveNavLink(panelKey);

                if (panelKey === "listings") {
                    if (!listingWizardStarted) {
                        listingWizardStarted = true;
                        listingWizardPanelStep = 1;
                    }
                    applyListingWizardVisibility();
                } else {
                    setListingPanelsHidden(true);
                }
            }

            function resolvePanelFromHash(hashValue) {
                const panelKey = String(hashValue || "").replace(/^#/, "").trim().toLowerCase();
                return validPanelKeys.has(panelKey) ? panelKey : "overview";
            }

            function focusListingsWizardStep(step) {
                const safeStep = Math.max(1, Math.min(4, Number(step) || 1));
                const stepTargets = {
                    1: "vendorPropertiesSection",
                    2: "vendorPropertiesSection",
                    3: "vendorRoomsSection",
                    4: "vendorMediaSection"
                };
                const targetId = stepTargets[safeStep] || "vendorPropertiesSection";
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;
                targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            function listingPanelStepFromWizardStep(step) {
                const safeStep = Math.max(1, Math.min(4, Number(step) || 1));
                if (safeStep >= 4) return 3;
                if (safeStep >= 3) return 2;
                return 1;
            }

            function setListingPanelsHidden(hidden) {
                listingStepPanels.forEach((panel) => {
                    panel.hidden = hidden;
                });
            }

            function applyListingWizardVisibility() {
                const activeStepPanel = Math.max(1, Math.min(3, Number(listingWizardPanelStep) || 1));
                listingStepPanels.forEach((panel) => {
                    const panelStep = Number(panel.getAttribute("data-listing-step") || "0");
                    panel.hidden = panelStep !== activeStepPanel;
                });
            }

            function activateListingWizardStep(wizardStep, shouldScroll) {
                listingWizardStarted = true;
                listingWizardPanelStep = listingPanelStepFromWizardStep(wizardStep);
                applyListingWizardVisibility();
                if (shouldScroll) {
                    focusListingsWizardStep(wizardStep);
                }
            }

            function guidedWizardCurrentFlow() {
                const flow = guidedWizardFlows[guidedWizardTrack];
                return Array.isArray(flow) ? flow : guidedWizardFlows.property;
            }

            function guidedWizardCanMoveToIndex(targetIndex) {
                const safeTargetIndex = Math.max(0, Number(targetIndex) || 0);

                if (guidedWizardTrack === "property") {
                    if (safeTargetIndex >= 1 && vendorPropertiesCount <= 0) {
                        return {
                            ok: false,
                            message: "Create at least one property to continue to review, room setup, and media steps.",
                        };
                    }

                    if (safeTargetIndex >= 3 && vendorRoomsCount <= 0) {
                        return {
                            ok: false,
                            message: "Add at least one room before progressing to media-focused property flow.",
                        };
                    }

                    if (safeTargetIndex >= 4 && !vendorBillingReady) {
                        return {
                            ok: false,
                            message: "Complete billing profile before final publish readiness.",
                        };
                    }
                }

                return { ok: true, message: "" };
            }

            function persistGuidedWizardState() {
                const payload = {
                    track: guidedWizardTrack,
                    index: guidedWizardIndex,
                    savedAt: Date.now(),
                };
                try {
                    sessionStorage.setItem(GUIDED_WIZARD_STORAGE_KEY, JSON.stringify(payload));
                } catch (error) {
                    // Ignore storage errors in private/incognito contexts.
                }
            }

            function restoreGuidedWizardState() {
                try {
                    const raw = sessionStorage.getItem(GUIDED_WIZARD_STORAGE_KEY);
                    if (!raw) {
                        return false;
                    }
                    const parsed = JSON.parse(raw);
                    const track = String(parsed.track || "").toLowerCase();
                    const index = Number(parsed.index);
                    if (!(track in guidedWizardFlows)) {
                        return false;
                    }
                    const flow = guidedWizardFlows[track];
                    if (!Array.isArray(flow) || flow.length === 0) {
                        return false;
                    }
                    guidedWizardTrack = track;
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, Number.isFinite(index) ? index : 0));
                    return true;
                } catch (error) {
                    return false;
                }
            }

            function applyGuidedWizardStep(shouldScroll) {
                const flow = guidedWizardCurrentFlow();
                const safeIndex = Math.max(0, Math.min(flow.length - 1, Number(guidedWizardIndex) || 0));
                guidedWizardIndex = safeIndex;
                const currentStep = flow[safeIndex];
                if (!currentStep) {
                    return;
                }

                showPanelGroup(String(currentStep.panel || "listings"));

                if (typeof currentStep.wizardStep === "number") {
                    activateListingWizardStep(currentStep.wizardStep, Boolean(shouldScroll));
                } else if (shouldScroll && currentStep.targetId) {
                    const target = document.getElementById(currentStep.targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: "smooth", block: "start" });
                    }
                }

                if (currentStep.openPropertyForm && propertyCreateForm) {
                    propertyCreateForm.hidden = false;
                    if (closePropertyCreateForm) {
                        closePropertyCreateForm.hidden = false;
                    }
                }

                if (currentStep.openRoomForm && roomCreateForm) {
                    roomCreateForm.hidden = false;
                    if (closeRoomCreateForm) {
                        closeRoomCreateForm.hidden = false;
                    }
                }
            }

            function renderGuidedWizard() {
                const flow = guidedWizardCurrentFlow();
                if (!guidedWizardSteps || !guidedWizardStepText || !guidedWizardProgressFill) {
                    return;
                }

                if (guidedTrackProperty) {
                    guidedTrackProperty.classList.toggle("is-active", guidedWizardTrack === "property");
                }

                guidedWizardSteps.innerHTML = "";
                flow.forEach((step, index) => {
                    const item = document.createElement("li");
                    item.className = "guided-step";
                    if (index < guidedWizardIndex) {
                        item.classList.add("is-complete");
                    }
                    if (index === guidedWizardIndex) {
                        item.classList.add("is-active");
                    }
                    item.textContent = "Step " + (index + 1) + ": " + step.title;
                    item.addEventListener("click", function () {
                        guidedWizardIndex = index;
                        renderGuidedWizard();
                        applyGuidedWizardStep(true);
                    });
                    guidedWizardSteps.appendChild(item);
                });

                const progressPercent = flow.length > 1
                    ? Math.round((guidedWizardIndex / (flow.length - 1)) * 100)
                    : 100;
                guidedWizardProgressFill.style.width = String(progressPercent) + "%";

                const activeStep = flow[guidedWizardIndex];
                guidedWizardStepText.textContent = "Step " + (guidedWizardIndex + 1) + " of " + flow.length + " - " + activeStep.hint;

                if (guidedWizardPrev) {
                    guidedWizardPrev.disabled = guidedWizardIndex <= 0;
                }
                if (guidedWizardNext) {
                    const isLastStep = guidedWizardIndex >= flow.length - 1;
                    guidedWizardNext.textContent = isLastStep ? "Go To Final Step" : "Next Step";

                    const targetIndex = Math.min(flow.length - 1, guidedWizardIndex + 1);
                    const gateCheck = guidedWizardCanMoveToIndex(targetIndex);
                    guidedWizardNext.disabled = !gateCheck.ok;
                    if (!gateCheck.ok) {
                        guidedWizardNext.title = gateCheck.message;
                        guidedWizardStepText.textContent = guidedWizardStepText.textContent + " | " + gateCheck.message;
                    } else {
                        guidedWizardNext.title = "";
                    }
                }

                persistGuidedWizardState();
            }

            const LOCATION_TREE = {
                "Maldives": {
                    "Kaafu Atoll": ["Male", "Hulhumale", "Maafushi"],
                    "Alif Alif Atoll": ["Rasdhoo", "Ukulhas", "Thoddoo"],
                    "Alif Dhaal Atoll": ["Dhigurah", "Dhangethi", "Mahibadhoo"],
                    "Baa Atoll": ["Eydhafushi", "Dharavandhoo", "Maalhos"]
                },
                "Sri Lanka": {
                    "Western Province": ["Colombo", "Negombo", "Kalutara"],
                    "Southern Province": ["Galle", "Matara", "Hambantota"],
                    "Central Province": ["Kandy", "Nuwara Eliya", "Matale"]
                },
                "India": {
                    "Kerala": ["Kochi", "Thiruvananthapuram", "Kozhikode"],
                    "Karnataka": ["Bengaluru", "Mysuru", "Mangaluru"],
                    "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai"]
                },
                "Other": {
                    "Other": ["Other"]
                }
            };

            function rebuildSelect(selectEl, values, placeholder) {
                if (!selectEl) return;
                selectEl.innerHTML = "";
                const defaultOption = document.createElement("option");
                defaultOption.value = "";
                defaultOption.textContent = placeholder;
                selectEl.appendChild(defaultOption);

                values.forEach((value) => {
                    const option = document.createElement("option");
                    option.value = value;
                    option.textContent = value;
                    selectEl.appendChild(option);
                });
            }

            function ensureSelectHasOption(selectEl, value) {
                if (!selectEl || !value) return;
                const exists = Array.from(selectEl.options).some((option) => option.value === value);
                if (exists) return;
                const option = document.createElement("option");
                option.value = value;
                option.textContent = value;
                selectEl.appendChild(option);
            }

            function refreshLocationSelectors() {
                if (!locationCountry || !locationState || !locationCity) return;
                const selectedCountry = locationCountry.dataset.selectedValue || locationCountry.value || "Maldives";
                ensureSelectHasOption(locationCountry, selectedCountry);
                locationCountry.value = selectedCountry;
                const country = locationCountry.value || "Maldives";
                const states = Object.keys(LOCATION_TREE[country] || {});
                rebuildSelect(locationState, states, "Select state/province");
                const selectedState = locationState.dataset.selectedValue || "";
                ensureSelectHasOption(locationState, selectedState);
                if (selectedState && Array.from(locationState.options).some((option) => option.value === selectedState)) {
                    locationState.value = selectedState;
                } else {
                    locationState.value = states[0] || "";
                }
                const cities = (LOCATION_TREE[country] || {})[locationState.value] || [];
                rebuildSelect(locationCity, cities, "Select city/island");
                const selectedCity = locationCity.dataset.selectedValue || "";
                ensureSelectHasOption(locationCity, selectedCity);
                if (selectedCity && Array.from(locationCity.options).some((option) => option.value === selectedCity)) {
                    locationCity.value = selectedCity;
                } else if (cities.length > 0) {
                    locationCity.value = cities[0];
                }

                locationCountry.dataset.selectedValue = "";
                locationState.dataset.selectedValue = "";
                locationCity.dataset.selectedValue = "";
            }

            function refreshCitySelector() {
                if (!locationCountry || !locationState || !locationCity) return;
                const country = locationCountry.value || "Maldives";
                const cities = (LOCATION_TREE[country] || {})[locationState.value] || [];
                const selectedCity = locationCity.dataset.selectedValue || "";
                rebuildSelect(locationCity, cities, "Select city/island");
                ensureSelectHasOption(locationCity, selectedCity);
                if (selectedCity && Array.from(locationCity.options).some((option) => option.value === selectedCity)) {
                    locationCity.value = selectedCity;
                } else if (cities.length > 0) {
                    locationCity.value = cities[0];
                }
                locationCity.dataset.selectedValue = "";
            }

            function normalizeCategoryKey(value) {
                return String(value || "")
                    .trim()
                    .toLowerCase()
                    .replace(/[\s-]+/g, "_")
                    .replace(/[^a-z0-9_]/g, "");
            }

            function categoryScopesFor(category) {
                const normalized = normalizeCategoryKey(category);

                if (normalized === "accommodation") {
                    return ["stay", "capacity"];
                }

                if (normalized === "transport") {
                    return ["capacity", "service", "transport"];
                }

                if (normalized === "excursion") {
                    return ["capacity", "service", "excursion"];
                }

                if (normalized === "remote_workspace") {
                    return ["capacity", "workspace", "stay"];
                }

                if (normalized === "resort_day_visit") {
                    return ["capacity", "day_visit"];
                }

                if (normalized === "restaurant") {
                    return ["capacity", "restaurant"];

                if (normalized === "excursion" || normalized === "resort_day_visit") {
                    return ["capacity", "experience", "service"];
                }

                if (normalized === "remote_workspace" || normalized === "restaurant") {
                    return ["capacity", "service"];
                }

                if (normalized === "transport" || normalized === "vehicle_rental") {
                    return ["vehicle", "capacity", "service"];
                }

                if (normalized === "vehicle_rental") {
                    return ["vehicle", "capacity", "rental"];
                }

                return ["stay", "capacity", "service", "vehicle", "transport", "excursion", "workspace", "day_visit", "restaurant", "rental"];
            }

            function refreshCategoryViewPanels() {
                if (!propertyCategorySelect || categoryViewPanels.length === 0) {
                    return;
                }
                const activeCategory = normalizeCategoryKey(propertyCategorySelect.value);
                categoryViewPanels.forEach((panel) => {
                    const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view'));
                    panel.hidden = panelCategory !== activeCategory;
                });
            }

            function categoryMetaFor(category) {
                const normalized = normalizeCategoryKey(category);
                const fallbackLabel = propertyCategorySelect
                    ? (propertyCategorySelect.options[propertyCategorySelect.selectedIndex]?.textContent || 'Listing')
                    : 'Listing';

                const categoryMeta = {
                    accommodation: {
                        title: 'Accommodation Enlist Form',
                        subtitle: 'Add stay-focused listing details, space setup, and guest capacity.',
                        submit: 'Save Accommodation Listing',
                        note: 'Accommodation fields are active for this category.',
                        propertyType: 'property',
                    },
                    transport: {
                        title: 'Transport Enlist Form',
                        subtitle: 'Add transfer and transport service listing details.',
                        submit: 'Save Transport Listing',
                        note: 'Transport-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                    excursion: {
                        title: 'Excursion Enlist Form',
                        subtitle: 'Add activity and guided experience listing details.',
                        submit: 'Save Excursion Listing',
                        note: 'Excursion-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                    remote_workspace: {
                        title: 'Remote Workspace Enlist Form',
                        subtitle: 'Add workspace listing details for remote workers and teams.',
                        submit: 'Save Remote Workspace Listing',
                        note: 'Remote workspace fields are active for this category.',
                        propertyType: 'service',
                    },
                    resort_day_visit: {
                        title: 'Resort Day Visit Enlist Form',
                        subtitle: 'Add day-visit package listing details for resort access.',
                        submit: 'Save Resort Day Visit Listing',
                        note: 'Resort day visit fields are active for this category.',
                        propertyType: 'service',
                    },
                    restaurant: {
                        title: 'Restaurant Enlist Form',
                        subtitle: 'Add restaurant listing details with seating and service scope.',
                        submit: 'Save Restaurant Listing',
                        note: 'Restaurant-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                    vehicle_rental: {
                        title: 'Vehicle Rental Enlist Form',
                        subtitle: 'Add rental fleet listing details with vehicle constraints.',
                        submit: 'Save Vehicle Rental Listing',
                        note: 'Vehicle-rental-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                };

                return categoryMeta[normalized] || {
                    title: fallbackLabel + ' Enlist Form',
                    subtitle: 'Add listing details specific to ' + fallbackLabel + '.',
                    submit: 'Save ' + fallbackLabel + ' Listing',
                    note: 'Category-specific fields will change based on your selection.',
                    propertyType: null,
                };
            }

            function applyCategoryFormMeta(category, forceType) {
                const meta = categoryMetaFor(category);
                if (propertyCreateFormTitle) {
                    propertyCreateFormTitle.textContent = meta.title;
                }
                if (propertyCreateFormSubtitle) {
                    propertyCreateFormSubtitle.textContent = meta.subtitle;
                }
                if (propertyCreateSubmitButton) {
                    propertyCreateSubmitButton.textContent = meta.submit;
                }
                if (propertyCategoryScopeNote) {
                    propertyCategoryScopeNote.textContent = meta.note;
                }
                if (forceType && propertyTypeSelect && meta.propertyType) {
                    ensureSelectHasOption(propertyTypeSelect, meta.propertyType);
                    propertyTypeSelect.value = meta.propertyType;
                }
            }

            function refreshCategoryViewPanels() {
                if (!propertyCategorySelect || categoryViewPanels.length === 0) {
                    return;
                }
                const activeCategory = normalizeCategoryKey(propertyCategorySelect.value);
                categoryViewPanels.forEach((panel) => {
                    const panelCategory = normalizeCategoryKey(panel.getAttribute('data-category-view'));
                    panel.hidden = panelCategory !== activeCategory;
                });
            }

            function categoryMetaFor(category) {
                const normalized = normalizeCategoryKey(category);
                const fallbackLabel = propertyCategorySelect
                    ? (propertyCategorySelect.options[propertyCategorySelect.selectedIndex]?.textContent || 'Listing')
                    : 'Listing';

                const categoryMeta = {
                    accommodation: {
                        title: 'Accommodation Enlist Form',
                        subtitle: 'Add stay-focused listing details, space setup, and guest capacity.',
                        submit: 'Save Accommodation Listing',
                        note: 'Accommodation fields are active for this category.',
                        propertyType: 'property',
                    },
                    transport: {
                        title: 'Transport Enlist Form',
                        subtitle: 'Add transfer and transport service listing details.',
                        submit: 'Save Transport Listing',
                        note: 'Transport-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                    excursion: {
                        title: 'Excursion Enlist Form',
                        subtitle: 'Add activity and guided experience listing details.',
                        submit: 'Save Excursion Listing',
                        note: 'Excursion-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                    remote_workspace: {
                        title: 'Remote Workspace Enlist Form',
                        subtitle: 'Add workspace listing details for remote workers and teams.',
                        submit: 'Save Remote Workspace Listing',
                        note: 'Remote workspace fields are active for this category.',
                        propertyType: 'service',
                    },
                    resort_day_visit: {
                        title: 'Resort Day Visit Enlist Form',
                        subtitle: 'Add day-visit package listing details for resort access.',
                        submit: 'Save Resort Day Visit Listing',
                        note: 'Resort day visit fields are active for this category.',
                        propertyType: 'service',
                    },
                    restaurant: {
                        title: 'Restaurant Enlist Form',
                        subtitle: 'Add restaurant listing details with seating and service scope.',
                        submit: 'Save Restaurant Listing',
                        note: 'Restaurant-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                    vehicle_rental: {
                        title: 'Vehicle Rental Enlist Form',
                        subtitle: 'Add rental fleet listing details with vehicle constraints.',
                        submit: 'Save Vehicle Rental Listing',
                        note: 'Vehicle-rental-focused fields are active for this category.',
                        propertyType: 'service',
                    },
                };

                return categoryMeta[normalized] || {
                    title: fallbackLabel + ' Enlist Form',
                    subtitle: 'Add listing details specific to ' + fallbackLabel + '.',
                    submit: 'Save ' + fallbackLabel + ' Listing',
                    note: 'Category-specific fields will change based on your selection.',
                    propertyType: null,
                };
            }

            function applyCategoryFormMeta(category, forceType) {
                const meta = categoryMetaFor(category);
                if (propertyCreateFormTitle) {
                    propertyCreateFormTitle.textContent = meta.title;
                }
                if (propertyCreateFormSubtitle) {
                    propertyCreateFormSubtitle.textContent = meta.subtitle;
                }
                if (propertyCreateSubmitButton) {
                    propertyCreateSubmitButton.textContent = meta.submit;
                }
                if (propertyCategoryScopeNote) {
                    propertyCategoryScopeNote.textContent = meta.note;
                }
                if (forceType && propertyTypeSelect && meta.propertyType) {
                    ensureSelectHasOption(propertyTypeSelect, meta.propertyType);
                    propertyTypeSelect.value = meta.propertyType;
                }
            }

            function refreshPropertyCategoryFields() {
                if (!propertyCategorySelect || categoryScopedFields.length === 0) return;
                const activeScopes = categoryScopesFor(propertyCategorySelect.value);
                categoryScopedFields.forEach((field) => {
                    const scopes = String(field.getAttribute("data-category-scope") || "")
                        .split(",")
                        .map((value) => value.trim().toLowerCase())
                        .filter(Boolean);
                    if (scopes.length === 0) {
                        field.hidden = false;
                        return;
                    }
                    field.hidden = !scopes.some((scope) => activeScopes.includes(scope));
                });
                refreshCategoryViewPanels();
                applyCategoryFormMeta(propertyCategorySelect.value, false);
            }

            function applyPropertyCategoryFilter(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || 'all');
                propertyListingRows.forEach((row) => {
                    const rowCategory = normalizeCategoryKey(row.getAttribute('data-listing-category') || '');
                    const shouldShow = normalizedCategory === 'all' || rowCategory === normalizedCategory;
                    row.hidden = !shouldShow;
                });

            }

            function openPropertyFlowWithCategory(categoryKey) {
                const normalizedCategory = normalizeCategoryKey(categoryKey || '');
                window.location.hash = 'listings';
                showPanelGroup('listings');
                activateListingWizardStep(1, true);

                if (propertyCreateForm) {
                    propertyCreateForm.hidden = false;
                }
                if (closePropertyCreateForm) {
                    closePropertyCreateForm.hidden = false;
                }
                if (propertyCategorySelect && normalizedCategory !== '') {
                    ensureSelectHasOption(propertyCategorySelect, normalizedCategory);
                    propertyCategorySelect.value = normalizedCategory;
                    propertyCategorySelect.dispatchEvent(new Event('change'));
                    applyCategoryFormMeta(normalizedCategory, true);
                }
                if (document.getElementById('property_name')) {
                    document.getElementById('property_name').focus();
                }

                applyPropertyCategoryFilter(normalizedCategory || 'all');
            }

            function refreshBillingLocationSelectors() {
                if (!billingCountry || !billingState || !billingCity) return;
                const country = billingCountry.value || "Maldives";
                const states = Object.keys(LOCATION_TREE[country] || {});
                const previousState = billingState.dataset.selectedValue || billingState.value;
                const previousCity = billingCity.dataset.selectedValue || billingCity.value;

                rebuildSelect(billingState, states, "Select state/province");
                ensureSelectHasOption(billingState, previousState);

                if (previousState && Array.from(billingState.options).some((option) => option.value === previousState)) {
                    billingState.value = previousState;
                } else if (states.length > 0) {
                    billingState.value = states[0];
                }

                const cities = (LOCATION_TREE[country] || {})[billingState.value] || [];
                rebuildSelect(billingCity, cities, "Select city/island");
                ensureSelectHasOption(billingCity, previousCity);

                if (previousCity && Array.from(billingCity.options).some((option) => option.value === previousCity)) {
                    billingCity.value = previousCity;
                } else if (cities.length > 0) {
                    billingCity.value = cities[0];
                }

                billingState.dataset.selectedValue = "";
                billingCity.dataset.selectedValue = "";
            }

            function refreshBillingCitySelector() {
                if (!billingCountry || !billingState || !billingCity) return;
                const country = billingCountry.value || "Maldives";
                const cities = (LOCATION_TREE[country] || {})[billingState.value] || [];
                const previousCity = billingCity.dataset.selectedValue || billingCity.value;
                rebuildSelect(billingCity, cities, "Select city/island");
                ensureSelectHasOption(billingCity, previousCity);
                if (previousCity && Array.from(billingCity.options).some((option) => option.value === previousCity)) {
                    billingCity.value = previousCity;
                } else if (cities.length > 0) {
                    billingCity.value = cities[0];
                }
                billingCity.dataset.selectedValue = "";
            }

            function initLocationMap() {
                if (!window.L) return;
                const mapEl = document.getElementById("propertyMap");
                if (!mapEl) return;

                const defaultLat = Number(mapLatitude && mapLatitude.value) || 4.1755;
                const defaultLng = Number(mapLongitude && mapLongitude.value) || 73.5093;
                const map = window.L.map(mapEl).setView([defaultLat, defaultLng], 9);

                window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    attribution: "&copy; OpenStreetMap contributors"
                }).addTo(map);

                let marker = window.L.marker([defaultLat, defaultLng]).addTo(map);

                function updateLocationFromMap(latlng) {
                    const lat = Number(latlng.lat.toFixed(6));
                    const lng = Number(latlng.lng.toFixed(6));

                    if (mapLatitude) mapLatitude.value = String(lat);
                    if (mapLongitude) mapLongitude.value = String(lng);
                    if (mapPlaceId && mapPlaceId.value.trim() === "") {
                        mapPlaceId.value = "PIN-" + lat + "," + lng;
                    }

                    marker.setLatLng([lat, lng]);
                }

                map.on("click", function (event) {
                    updateLocationFromMap(event.latlng);
                });
            }

            async function refreshSummary() {
                const token = getToken();
                if (!token) {
                    setSummaryDefaults();
                    return;
                }

                if (refreshSummaryBtn) refreshSummaryBtn.disabled = true;

                const verdict = evaluateToken(token);
                if (summaryToken) {
                    summaryToken.textContent = verdict.stateText.replace("TOKEN ", "");
                }
                if (summaryTokenMeta) {
                    summaryTokenMeta.textContent = verdict.metaText;
                }

                try {
                    const [bookingsResult, settlementsResult] = await Promise.all([
                        fetchJsonWithAuth("/api/v1/bookings", token),
                        fetchJsonWithAuth("/api/v1/payments/vendor/me/settlements/report", token),
                    ]);

                    const bookingsCount = deriveCount(bookingsResult.json);
                    const settlementsCount = deriveCount(settlementsResult.json);
                    if (summaryBookings) {
                        summaryBookings.textContent = bookingsCount === null ? "N/A" : String(bookingsCount);
                    }
                    if (summarySettlements) {
                        summarySettlements.textContent = settlementsCount === null ? "N/A" : String(settlementsCount);
                    }

                    renderPayoutCenter(settlementsResult.json);

                    const nowText = new Date().toLocaleString();
                    if (bookingsResult.ok || settlementsResult.ok) {
                        setConnectivity("ok", "ONLINE", nowText);
                    } else if (bookingsResult.status === 401 || bookingsResult.status === 403 || settlementsResult.status === 401 || settlementsResult.status === 403) {
                        setConnectivity("warn", "AUTH ISSUE", nowText);
                    } else {
                        setConnectivity("err", "OFFLINE", nowText);
                    }
                } catch (error) {
                    setConnectivity("err", "OFFLINE", new Date().toLocaleString());
                    if (summaryBookings) summaryBookings.textContent = "N/A";
                    if (summarySettlements) summarySettlements.textContent = "N/A";
                } finally {
                    if (refreshSummaryBtn) refreshSummaryBtn.disabled = false;
                }
            }

            async function run(path, triggerButton) {
                const token = getToken();
                if (!token) {
                    setState("warn", "TOKEN REQUIRED");
                    setMeta("Save a vendor token before running requests.");
                    output.textContent = "Save a vendor token first.";
                    return;
                }

                const verdict = evaluateToken(token);
                if (!verdict.isUsable) {
                    setState(verdict.stateType, verdict.stateText);
                    setMeta(verdict.metaText);
                    output.textContent = "Token is expired or invalid. Save a fresh vendor token first.";
                    return;
                }

                const button = triggerButton || null;
                if (button) {
                    button.disabled = true;
                    button.classList.add("is-loading");
                    if (!button.dataset.label) {
                        button.dataset.label = button.textContent || "Run";
                    }
                    button.textContent = "Running";
                }

                output.textContent = "Loading " + path + " ...";
                try {
                    const res = await fetch(apiBase + path, {
                        method: "GET",
                        headers: {
                            "Authorization": "Bearer " + token,
                            "Accept": "application/json"
                        },
                        cache: "no-store"
                    });
                    const text = await res.text();
                    let parsed = text;
                    try {
                        parsed = JSON.stringify(JSON.parse(text), null, 2);
                    } catch (error) {
                        // Keep plain text if response is not JSON.
                    }
                    output.textContent = "Status: " + res.status + "\n\n" + parsed;
                    if (res.ok) {
                        applyTokenFeedback(token, "ok", "TOKEN VALID");
                    } else if (res.status === 401 || res.status === 403) {
                        setState("err", "TOKEN INVALID FOR VENDOR");
                        setMeta("The API rejected this token for vendor access.");
                    } else {
                        applyTokenFeedback(token, "warn", "REQUEST COMPLETED WITH WARNINGS");
                    }
                } catch (error) {
                    setState("err", "REQUEST FAILED");
                    setMeta("Request failed before token validation could complete.");
                    output.textContent = "Network/CORS error. Ensure API allows origin https://www.workation.mv\n\n" + String(error);
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.classList.remove("is-loading");
                        button.textContent = button.dataset.label || "Run";
                    }
                }
            }

            document.getElementById("saveToken").addEventListener("click", saveToken);
            document.getElementById("clearToken").addEventListener("click", clearToken);
            if (refreshSummaryBtn) {
                refreshSummaryBtn.addEventListener("click", refreshSummary);
            }
            tokenInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    saveToken();
                }
            });
            document.querySelectorAll("button[data-path]").forEach((button) => {
                button.addEventListener("click", function () {
                    run(button.getAttribute("data-path"), button);
                });
            });

            navLinks.forEach((link) => {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    const panelKey = String(link.dataset.panelKey || "").trim().toLowerCase();
                    if (!panelKey) return;
                    window.location.hash = panelKey;
                    showPanelGroup(panelKey);
                });
            });

            window.addEventListener("hashchange", function () {
                showPanelGroup(resolvePanelFromHash(window.location.hash));
            });

            if (guidedTrackProperty) {
                guidedTrackProperty.addEventListener("click", function () {
                    guidedWizardTrack = "property";
                    guidedWizardIndex = 0;
                    window.location.hash = "listings";
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (guidedWizardPrev) {
                guidedWizardPrev.addEventListener("click", function () {
                    const flow = guidedWizardCurrentFlow();
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, guidedWizardIndex - 1));
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (guidedWizardResume) {
                guidedWizardResume.addEventListener("click", function () {
                    if (restoreGuidedWizardState()) {
                        window.location.hash = "listings";
                        renderGuidedWizard();
                        applyGuidedWizardStep(true);
                    }
                });
            }

            if (guidedWizardNext) {
                guidedWizardNext.addEventListener("click", function () {
                    const flow = guidedWizardCurrentFlow();
                    guidedWizardIndex = Math.max(0, Math.min(flow.length - 1, guidedWizardIndex + 1));
                    renderGuidedWizard();
                    applyGuidedWizardStep(true);
                });
            }

            if (openPropertyCreateForm && propertyCreateForm) {
                openPropertyCreateForm.addEventListener("click", function () {
                    propertyCreateForm.hidden = false;
                    if (closePropertyCreateForm) closePropertyCreateForm.hidden = false;
                    if (propertyCategorySelect) propertyCategorySelect.focus();
                });
            }

            if (closePropertyCreateForm && propertyCreateForm) {
                closePropertyCreateForm.addEventListener("click", function () {
                    propertyCreateForm.hidden = true;
                    closePropertyCreateForm.hidden = true;
                });
            }

            if (openRoomCreateForm && roomCreateForm) {
                openRoomCreateForm.addEventListener("click", function () {
                    roomCreateForm.hidden = false;
                    if (closeRoomCreateForm) closeRoomCreateForm.hidden = false;
                    if (roomPropertySelect) roomPropertySelect.focus();
                });
            }

            if (closeRoomCreateForm && roomCreateForm) {
                closeRoomCreateForm.addEventListener("click", function () {
                    roomCreateForm.hidden = true;
                    closeRoomCreateForm.hidden = true;
                });
            }

            roomQuickOpenButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    const propertyId = String(button.getAttribute("data-property-id") || "").trim();
                    window.location.hash = "listings";
                    showPanelGroup("listings");
                    activateListingWizardStep(3, true);

                    if (roomCreateForm) roomCreateForm.hidden = false;
                    if (closeRoomCreateForm) closeRoomCreateForm.hidden = false;

                    if (roomPropertySelect && propertyId) {
                        ensureSelectHasOption(roomPropertySelect, propertyId);
                        roomPropertySelect.value = propertyId;
                        roomPropertySelect.dispatchEvent(new Event("change"));
                        roomPropertySelect.focus();
                    }
                });
            });

            function applyPropertyEditScope(form, category) {
                if (!form) {
                    return;
                }
                const activeScopes = categoryScopesFor(category);
                form.querySelectorAll('[data-property-edit-scope]').forEach((field) => {
                    const scope = String(field.getAttribute('data-property-edit-scope') || '').trim().toLowerCase();
                    const shouldShow = scope !== '' && activeScopes.includes(scope);
                    field.hidden = !shouldShow;
                    field.disabled = !shouldShow;
                });
            }

            function openEditForm(selector) {
                const form = document.querySelector(selector);
                if (!form) {
                    return;
                }
                const category = String(form.getAttribute('data-property-edit-category') || '').trim();
                if (category !== '') {
                    applyPropertyEditScope(form, category);
                }
                form.hidden = false;
                const firstInput = form.querySelector('input, select, textarea');
                if (firstInput) {
                    firstInput.focus();
                }
            }

            function closeEditForm(selector) {
                const form = document.querySelector(selector);
                if (!form) {
                    return;
                }
                form.hidden = true;
            }

            propertyEditButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    const category = String(button.getAttribute('data-property-edit-category') || '').trim();
                    const selector = '[data-property-edit-form="' + editId + '"]';
                    const form = document.querySelector(selector);
                    if (form && category !== '') {
                        applyPropertyEditScope(form, category);
                    }
                    openEditForm(selector);
                });
            });

            propertyEditCancelButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-property-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    closeEditForm('[data-property-edit-form="' + editId + '"]');
                });
            });

            roomEditButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-room-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    openEditForm('[data-room-edit-form="' + editId + '"]');
                });
            });

            roomEditCancelButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const editId = String(button.getAttribute('data-room-edit-id') || '').trim();
                    if (!editId) {
                        return;
                    }
                    closeEditForm('[data-room-edit-form="' + editId + '"]');
                });
            });

            listingCategoryShortcutButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const categoryKey = String(button.getAttribute('data-listing-category-shortcut') || '');
                    openPropertyFlowWithCategory(categoryKey);
                });
            });

            document.querySelectorAll('.js-row-update').forEach((button) => {
                button.addEventListener('click', function (event) {
                    const form = button.closest('form');
                    if (!form) {
                        return;
                    }
                    event.preventDefault();
                    if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                        return;
                    }
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });

            if (locationCountry && locationState && locationCity) {
                locationCountry.dataset.selectedValue = "x";
                locationState.dataset.selectedValue = "x";
                locationCity.dataset.selectedValue = "x";
                refreshLocationSelectors();
                locationCountry.addEventListener("change", refreshLocationSelectors);
                locationState.addEventListener("change", refreshCitySelector);
            }

            if (propertyCategorySelect) {
                refreshPropertyCategoryFields();
                propertyCategorySelect.addEventListener("change", refreshPropertyCategoryFields);
            }

            if (billingCountry && billingState && billingCity) {
                billingState.dataset.selectedValue = "x";
                billingCity.dataset.selectedValue = "x";
                refreshBillingLocationSelectors();
                billingCountry.addEventListener("change", refreshBillingLocationSelectors);
                billingState.addEventListener("change", refreshBillingCitySelector);
            }
            initLocationMap();

            setInterval(function () {
                const token = getToken();
                if (token) {
                    applyTokenFeedback(token);
                }
            }, 60000);

            if (getToken()) {
                applyTokenFeedback(getToken());
                refreshSummary();
            } else {
                setMeta("Token is stored only in this browser tab session.");
                setSummaryDefaults();
            }

            const hashPanelKey = resolvePanelFromHash(window.location.hash || "#overview");
            const initialPanelKey = serverPanelKey && validPanelKeys.has(serverPanelKey) ? serverPanelKey : hashPanelKey;
            listingWizardPanelStep = listingPanelStepFromWizardStep(listingWizardStep);
            showPanelGroup(initialPanelKey);
            restoreGuidedWizardState();
            renderGuidedWizard();
            applyPropertyCategoryFilter('all');
            if (initialPanelKey === "listings") {
                if (serverPanelKey === "listings") {
                    activateListingWizardStep(listingWizardStep, true);
                } else {
                    applyListingWizardVisibility();
                }
            }
        })();
