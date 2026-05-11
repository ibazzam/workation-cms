<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'Workation' }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f8f5;font-family:Arial,Helvetica,sans-serif;color:#152738;">
    @php
        $branding = $branding ?? workationBrandingProfile();
        $logoUrl = trim((string) ($branding['logo_url'] ?? ''));
        $brandName = trim((string) ($branding['name'] ?? 'Workation'));
        $brandTagline = trim((string) ($branding['tagline'] ?? 'Stay, work, and travel.'));
        $supportEmail = trim((string) ($branding['support_email'] ?? 'support@workation.com'));
        $brandMobile = trim((string) ($branding['mobile'] ?? ''));
        $brandHotline = trim((string) ($branding['hotline'] ?? ''));
        $brandUrl = trim((string) ($branding['url'] ?? url('/')));
        $headline = trim((string) ($headline ?? ''));
        $intro = trim((string) ($intro ?? ''));
        $statusLabel = trim((string) ($statusLabel ?? ''));
        $statusTone = trim((string) ($statusTone ?? 'info'));
        $bodyLines = collect($bodyLines ?? [])->map(static fn ($line) => trim((string) $line))->filter()->values();
        $ctaUrl = trim((string) ($ctaUrl ?? ''));
        $ctaLabel = trim((string) ($ctaLabel ?? 'View in Workation'));
        $supportEmail = trim((string) ($supportEmail ?? config('mail.from.address', 'support@workation.com')));
        $toneMap = [
            'success' => ['bg' => '#e7f8ef', 'fg' => '#126b3f', 'border' => '#b7ebce'],
            'warning' => ['bg' => '#fff4df', 'fg' => '#8a5800', 'border' => '#ffd89d'],
            'danger' => ['bg' => '#fdecef', 'fg' => '#a12d45', 'border' => '#f7b9c4'],
            'info' => ['bg' => '#e7f3f8', 'fg' => '#0f6179', 'border' => '#b9deea'],
        ];
        $tone = $toneMap[$statusTone] ?? $toneMap['info'];
    @endphp
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;line-height:1px;">
        {{ trim((string) ($preheader ?? $headline ?: 'Workation update')) }}
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f8f5;width:100%;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;background:#ffffff;border:1px solid #d8e3ec;border-radius:18px;overflow:hidden;box-shadow:0 14px 32px rgba(21,39,56,0.08);">
                    <tr>
                        <td style="padding:26px 28px 18px;background:linear-gradient(135deg,#0f6179 0%,#0b4f66 100%);color:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        @if ($logoUrl !== '')
                                            <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" style="display:block;max-height:54px;max-width:180px;object-fit:contain;margin-bottom:10px;">
                                        @else
                                            <div style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:14px;background:rgba(255,255,255,0.16);border:1px solid rgba(255,255,255,0.22);font-size:18px;font-weight:700;letter-spacing:0.04em;">W</div>
                                        @endif
                                        <div style="margin-top:10px;font-size:13px;letter-spacing:0.22em;text-transform:uppercase;opacity:0.88;">{{ $brandName }}</div>
                                        @if ($brandTagline !== '')
                                            <div style="margin-top:6px;font-size:12px;line-height:1.4;opacity:0.82;">{{ $brandTagline }}</div>
                                        @endif
                                        <div style="font-size:22px;line-height:1.2;font-weight:700;margin-top:8px;">{{ $headline ?: 'Account update' }}</div>
                                        @if ($intro !== '')
                                            <div style="margin-top:8px;font-size:15px;line-height:1.6;opacity:0.95;max-width:540px;">{{ $intro }}</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 28px 10px;">
                            @if ($statusLabel !== '')
                                <div style="display:inline-block;margin-bottom:18px;padding:8px 12px;border-radius:999px;background:{{ $tone['bg'] }};border:1px solid {{ $tone['border'] }};color:{{ $tone['fg'] }};font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;">
                                    {{ $statusLabel }}
                                </div>
                            @endif

                            @foreach ($bodyLines as $line)
                                <p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:#152738;">{{ $line }}</p>
                            @endforeach

                            @if (!empty($metaRows) && is_array($metaRows))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:18px 0 6px;border-collapse:separate;border-spacing:0;">
                                    @foreach ($metaRows as $label => $value)
                                        @php
                                            $labelText = trim((string) $label);
                                            $valueText = trim((string) $value);
                                        @endphp
                                        @if ($labelText !== '' || $valueText !== '')
                                            <tr>
                                                <td style="padding:10px 12px;border-top:1px solid #e2ebf2;width:160px;font-size:13px;font-weight:700;color:#3c556b;vertical-align:top;">{{ $labelText }}</td>
                                                <td style="padding:10px 12px;border-top:1px solid #e2ebf2;font-size:14px;color:#152738;line-height:1.6;">{{ $valueText !== '' ? $valueText : '—' }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            @endif

                            @if ($ctaUrl !== '')
                                <div style="padding-top:18px;">
                                    <a href="{{ $ctaUrl }}" style="display:inline-block;padding:12px 18px;border-radius:12px;background:#0f6179;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">{{ $ctaLabel }}</a>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 28px 28px;">
                            <div style="height:1px;background:#e4ebf1;margin:8px 0 16px;"></div>
                            <p style="margin:0 0 8px;font-size:12px;line-height:1.7;color:#607486;">
                                This is an automated message from Workation. Please do not reply to this email.
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.7;color:#607486;">
                                Need help? Contact {{ $supportEmail }} or visit <a href="{{ $brandUrl }}" style="color:#0f6179;text-decoration:none;">{{ $brandUrl }}</a>.
                            </p>
                            @if ($brandMobile !== '' || $brandHotline !== '')
                                <p style="margin:6px 0 0;font-size:12px;line-height:1.7;color:#607486;">
                                    @if ($brandMobile !== '')
                                        Mobile: {{ $brandMobile }}
                                    @endif
                                    @if ($brandHotline !== '')
                                        @if ($brandMobile !== '') · @endif
                                        Hotline: {{ $brandHotline }}
                                    @endif
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>