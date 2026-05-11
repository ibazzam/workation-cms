@php
    $branding = $branding ?? workationBrandingProfile();
    $logoUrl = trim((string) ($branding['logo_url'] ?? ''));
    $brandName = trim((string) ($branding['name'] ?? 'Workation'));
    $tagline = trim((string) ($branding['tagline'] ?? 'Stay, work, and travel.'));
    $supportEmail = trim((string) ($branding['support_email'] ?? 'support@workation.com'));
    $brandMobile = trim((string) ($branding['mobile'] ?? ''));
    $brandHotline = trim((string) ($branding['hotline'] ?? ''));
    $brandUrl = trim((string) ($branding['url'] ?? url('/')));
    $addressLines = array_values(array_filter((array) ($branding['address_lines'] ?? []), static fn ($line) => trim((string) $line) !== ''));
@endphp

<table width="100%" cellspacing="0" cellpadding="0" border="0" style="border-bottom:2px solid {{ $branding['accent'] ?? '#0f6179' }}; margin-bottom:18px; padding-bottom:14px;">
    <tr>
        <td style="vertical-align:middle; width:72%;">
            <table cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="vertical-align:middle; padding-right:12px;">
                        @if ($logoUrl !== '')
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" style="display:block;max-height:54px;max-width:180px;object-fit:contain;">
                        @else
                            <div style="display:inline-flex;align-items:center;justify-content:center;width:54px;height:54px;border-radius:14px;background:{{ $branding['accent'] ?? '#0f6179' }};color:#ffffff;font-size:18px;font-weight:700;letter-spacing:0.04em;">W</div>
                        @endif
                    </td>
                    <td style="vertical-align:middle;">
                        <div style="font-size:18px;font-weight:700;color:#152738;line-height:1.2;">{{ $brandName }}</div>
                        @if ($tagline !== '')
                            <div style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:{{ $branding['muted'] ?? '#607486' }};margin-top:3px;">{{ $tagline }}</div>
                        @endif
                        <div style="font-size:11px;color:{{ $branding['muted'] ?? '#607486' }};margin-top:5px;line-height:1.5;">
                            @if ($brandUrl !== '')
                                {{ $brandUrl }}
                            @endif
                            @if ($supportEmail !== '')
                                @if ($brandUrl !== '') · @endif
                                {{ $supportEmail }}
                            @endif
                        </div>
                        @if ($brandMobile !== '' || $brandHotline !== '')
                            <div style="font-size:11px;color:{{ $branding['muted'] ?? '#607486' }};margin-top:4px;line-height:1.5;">
                                @if ($brandMobile !== '')
                                    Mobile: {{ $brandMobile }}
                                @endif
                                @if ($brandHotline !== '')
                                    @if ($brandMobile !== '') · @endif
                                    Hotline: {{ $brandHotline }}
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
        <td style="vertical-align:middle;text-align:right;width:28%;">
            <div style="font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:{{ $branding['accent'] ?? '#0f6179' }};">{{ $isConfirmation ? 'Reservation Confirmation' : 'Tax Invoice' }}</div>
            <div style="font-size:11px;color:{{ $branding['muted'] ?? '#607486' }};margin-top:6px;line-height:1.5;">Generated {{ (string) ($generated_at ?? '') }}</div>
        </td>
    </tr>
</table>

@if (!empty($addressLines))
    <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px;">
        <tr>
            <td style="font-size:10px;line-height:1.5;color:{{ $branding['muted'] ?? '#607486' }};">
                {{ implode(' · ', $addressLines) }}
            </td>
        </tr>
    </table>
@endif