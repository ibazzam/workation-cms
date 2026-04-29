@php
    $bookingProcessCurrentStep = max(1, min(4, (int) ($bookingProcessCurrentStep ?? 1)));
    $bookingProcessTitle = trim((string) ($bookingProcessTitle ?? 'Booking Process Highlights'));
    $bookingProcessBackUrl = trim((string) ($bookingProcessBackUrl ?? url()->previous('/')));
    $bookingProcessNextText = trim((string) ($bookingProcessNextText ?? ''));
    $bookingProcessSteps = [
        1 => '1. Guest Details',
        2 => '2. Transfer Selection',
        3 => '3. Payment Method',
        4 => '4. Final Confirmation',
    ];
@endphp

<style>
    .booking-page-header {
        margin: 0 0 8px;
        border-radius: 18px;
        padding: 16px;
        background: linear-gradient(135deg, #1a7588 0%, #2f9b95 100%);
        color: #f4fcff;
        box-shadow: 0 14px 30px rgba(15, 97, 121, 0.14);
    }

    .bph-back {
        display: inline-block;
        margin-bottom: 8px;
        color: #e2f8ff;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
    }

    .bph-back:hover { text-decoration: underline; }

    .bph-process-title {
        margin: 0 0 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.8rem;
        font-weight: 800;
    }

    .bph-steps {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .bph-step {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 8px 14px;
        border-radius: 999px;
        border: 1px solid rgba(226, 248, 255, 0.45);
        color: rgba(244, 252, 255, 0.88);
        font-size: 0.88rem;
        font-weight: 600;
    }

    .bph-step.current {
        background: #ffffff;
        color: #15536c;
        border-color: #ffffff;
        box-shadow: 0 8px 18px rgba(6, 38, 55, 0.12);
    }

    .bph-next {
        margin: 12px 0 0;
        color: #ebfbff;
        font-size: 0.92rem;
        font-weight: 500;
    }
</style>

<header class="booking-page-header" aria-label="Booking process highlights">
    <a class="bph-back" href="{{ $bookingProcessBackUrl !== '' ? $bookingProcessBackUrl : '/' }}">&larr; Back to property</a>
    <p class="bph-process-title">{{ $bookingProcessTitle }}</p>
    <div class="bph-steps" aria-label="Booking progress">
        @foreach ($bookingProcessSteps as $stepNumber => $stepLabel)
            <span class="bph-step {{ $bookingProcessCurrentStep === $stepNumber ? 'current' : '' }}">{{ $stepLabel }}</span>
        @endforeach
    </div>
    @if ($bookingProcessNextText !== '')
        <p class="bph-next">{{ $bookingProcessNextText }}</p>
    @endif
</header>