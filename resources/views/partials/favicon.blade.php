@php
    $faviconProfile = workationFaviconProfile();
    $faviconUrl = trim((string) ($faviconProfile['favicon_url'] ?? ''));
    $defaultFaviconUrl = asset('favicon.ico');
@endphp

@if ($faviconUrl !== '')
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">
@else
    <link rel="icon" type="image/x-icon" href="{{ $defaultFaviconUrl }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $defaultFaviconUrl }}">
@endif