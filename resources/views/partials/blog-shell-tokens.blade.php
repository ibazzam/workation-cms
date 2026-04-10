{{-- Shared visual shell tokens for blog surfaces to align with core app theme. --}}
<style>
    :root {
        --bg: #f3f8f5;
        --ink: #152738;
        --muted: #5f7488;
        --line: #d5e2ec;
        --surface: #ffffff;
        --surface-soft: #edf6f3;
        --brand: #0f6179;
        --brand-soft: #dff1f6;
        --accent-green: #43be66;
        --chip-line: #5ad176;
    }

    body {
        margin: 0;
        font-family: "Outfit", "Trebuchet MS", sans-serif;
        color: var(--ink);
        background: linear-gradient(180deg, #f8fbff 0%, #f4f8fc 26%, #f7fbf8 100%);
    }

    .page {
        width: min(1180px, calc(100% - 24px));
        margin: 0 auto 28px;
    }

    .header-bar {
        min-height: 84px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        background: var(--surface);
        border-bottom: 1px solid var(--line);
        position: sticky;
        top: 0;
        z-index: 30;
        padding: 0 10px;
    }

    .brand {
        margin: 0;
        text-decoration: none;
        font-size: 2rem;
        line-height: 1;
        letter-spacing: -0.04em;
        color: var(--brand);
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .brand small {
        color: var(--ink);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        font-weight: 700;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .nav-links a {
        text-decoration: none;
        color: var(--ink);
        font-size: 0.88rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 8px 10px;
        border-radius: 8px;
    }

    .nav-links a.is-active {
        color: var(--brand);
    }

    .nav-links a:hover {
        background: var(--brand-soft);
        color: var(--brand);
    }
</style>
