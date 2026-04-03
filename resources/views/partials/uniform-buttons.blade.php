<style>
    :root {
        --btn-primary-border: #f6d19a;
        --btn-primary-bg: linear-gradient(135deg, #ffc76f 0%, #f3a337 100%);
        --btn-primary-text: #57350b;
        --btn-secondary-border: #c8d8e5;
        --btn-secondary-bg: #ffffff;
        --btn-secondary-text: #20415d;
        --btn-danger-bg: #a33535;
        --btn-danger-text: #ffffff;
        --btn-radius: 10px;
        --btn-pad-y: 9px;
        --btn-pad-x: 12px;
        --btn-min-height: 38px;
        --btn-font-size: 0.86rem;
    }

    button,
    input[type="submit"],
    .btn,
    a.btn,
    .auth-link,
    .actions a,
    .actions button,
    .topbar-btn,
    .auth-btn,
    .hero-btn,
    .promo-banner a,
    .booking-item-link,
    .booking-category-btn,
    .quick-filter-btn,
    .booking-action,
    .top-link-btn,
    .reviews-load-btn,
    .room-offer-expand-btn,
    .btn-approve,
    .btn-reject,
    .filters button,
    .endpoint button,
    .registration-actions button,
    .manage-form button {
        border: 1px solid var(--btn-secondary-border);
        border-radius: var(--btn-radius);
        background: var(--btn-secondary-bg);
        color: var(--btn-secondary-text);
        text-decoration: none;
        padding: var(--btn-pad-y) var(--btn-pad-x);
        font: inherit;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: var(--btn-min-height);
        font-size: var(--btn-font-size);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .submit,
    .promo-apply,
    .hero-avail-btn {
        border: 1px solid var(--btn-secondary-border);
        border-radius: var(--btn-radius);
        background: var(--btn-secondary-bg);
        color: var(--btn-secondary-text);
        padding: var(--btn-pad-y) var(--btn-pad-x);
        min-height: var(--btn-min-height);
        font: inherit;
        font-size: var(--btn-font-size);
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    button:hover,
    input[type="submit"]:hover,
    .btn:hover,
    a.btn:hover,
    .auth-link:hover,
    .actions a:hover,
    .actions button:hover,
    .topbar-btn:hover,
    .auth-btn:hover,
    .hero-btn:hover,
    .promo-banner a:hover,
    .booking-item-link:hover,
    .booking-category-btn:hover,
    .quick-filter-btn:hover,
    .booking-action:hover,
    .top-link-btn:hover,
    .reviews-load-btn:hover,
    .room-offer-expand-btn:hover,
    .btn-approve:hover,
    .btn-reject:hover,
    .filters button:hover,
    .endpoint button:hover,
    .registration-actions button:hover,
    .manage-form button:hover {
        border-color: #97b7ce;
        box-shadow: 0 6px 14px rgba(22, 70, 102, 0.14);
        transform: translateY(-1px);
    }

    .submit:hover,
    .promo-apply:hover,
    .hero-avail-btn:hover {
        border-color: #97b7ce;
        box-shadow: 0 6px 14px rgba(22, 70, 102, 0.14);
        transform: translateY(-1px);
    }

    button:focus-visible,
    input[type="submit"]:focus-visible,
    .btn:focus-visible,
    a.btn:focus-visible,
    .auth-link:focus-visible,
    .actions a:focus-visible,
    .actions button:focus-visible,
    .topbar-btn:focus-visible,
    .auth-btn:focus-visible,
    .hero-btn:focus-visible,
    .promo-banner a:focus-visible,
    .booking-item-link:focus-visible,
    .booking-category-btn:focus-visible,
    .quick-filter-btn:focus-visible,
    .booking-action:focus-visible,
    .top-link-btn:focus-visible,
    .reviews-load-btn:focus-visible,
    .room-offer-expand-btn:focus-visible,
    .btn-approve:focus-visible,
    .btn-reject:focus-visible,
    .filters button:focus-visible,
    .endpoint button:focus-visible,
    .registration-actions button:focus-visible,
    .manage-form button:focus-visible {
        outline: 2px solid #0f6179;
        outline-offset: 2px;
    }

    .submit:focus-visible,
    .promo-apply:focus-visible,
    .hero-avail-btn:focus-visible {
        outline: 2px solid #0f6179;
        outline-offset: 2px;
    }

    .primary,
    button.primary,
    .btn-primary,
    .btn.primary,
    .actions button.primary,
    .actions a.primary,
    .btn-go,
    .btn-approve,
    .submit,
    .hero-avail-btn {
        border-color: var(--btn-primary-border);
        background: var(--btn-primary-bg);
        color: var(--btn-primary-text);
    }

    .btn-secondary,
    .btn.alt,
    .actions a.alt {
        border-color: var(--btn-secondary-border);
        background: #f7fbff;
        color: #244a65;
    }

    .btn-danger,
    .btn-reject,
    .danger,
    button.danger {
        border-color: #8f2f2f;
        background: var(--btn-danger-bg);
        color: var(--btn-danger-text);
    }

    button[disabled],
    input[type="submit"][disabled],
    .btn[disabled],
    .btn.is-disabled {
        opacity: 0.65;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
</style>