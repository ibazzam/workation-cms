{{--
  resources/views/admin/finance/_layout.blade.php
  Shared partial: <head>, base CSS, header nav.
  Usage:  @include('admin.finance._layout', ['pageTitle' => '...', 'activeNav' => '...'])
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $pageTitle ?? 'Finance' }} — Admin Portal</title>
<link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
:root{--bg:#edf4f2;--ink:#16212e;--muted:#5b6778;--card:#fffefb;--line:#d7e0e6;--hero-1:#183d64;--hero-2:#116b86;--hero-3:#1a9a7f;--ok:#0b5c2a;--ok-bg:#d8f7e2;--warn:#7a4606;--warn-bg:#ffeccd;--err:#6d1111;--err-bg:#ffe0de;}
*{box-sizing:border-box;}
body{margin:0;font-family:"Outfit","Trebuchet MS",sans-serif;color:var(--ink);background:radial-gradient(circle at 8% 10%,#d4ebff 0,#d4ebff00 32%),radial-gradient(circle at 90% 10%,#dff5e8 0,#dff5e800 35%),var(--bg);}
.page{width:min(1260px,calc(100% - 24px));margin:14px auto 40px;padding:0;}
.hero{background:linear-gradient(130deg,var(--hero-1) 0%,var(--hero-2) 48%,var(--hero-3) 100%);border-radius:12px;color:#fff;padding:14px 18px;box-shadow:0 10px 24px rgba(18,38,58,.18);}
.hero h1{margin:0 0 5px;font-size:clamp(1.1rem,2vw,1.5rem);}
.hero p{margin:0;color:#dcf4f3;font-size:.85rem;}
.hero-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;}
.hero-nav{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;}
.hero-nav a{color:#ecfbff;text-decoration:none;border:1px solid #b8dfe4;border-radius:9px;padding:7px 11px;font-size:.8rem;background:rgba(11,49,75,.32);transition:background .15s;}
.hero-nav a:hover,.hero-nav a.active{background:rgba(11,49,75,.55);border-color:#8ecbdb;}
.shell{margin-top:10px;}
.section{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;margin-top:10px;}
.section-title{margin:0 0 12px;font-family:"Space Grotesk","Trebuchet MS",sans-serif;font-size:.75rem;letter-spacing:.11em;text-transform:uppercase;color:var(--muted);}
/* Chips / badges */
.chip{display:inline-block;border-radius:999px;padding:3px 9px;font-size:.72rem;font-weight:700;}
.chip-ok{color:var(--ok);background:var(--ok-bg);}
.chip-warn{color:var(--warn);background:var(--warn-bg);}
.chip-err{color:var(--err);background:var(--err-bg);}
.chip-blue{color:#0a3d6b;background:#d8eeff;}
.chip-purple{color:#3d0a6b;background:#ece8ff;}
.chip-teal{color:#0a4d4b;background:#d5f5f0;}
.chip-grey{color:#3d4d5a;background:#e8edf2;}
/* Table */
.tbl-wrap{border:1px solid var(--line);border-radius:10px;overflow:hidden;margin-top:8px;}
.tbl{width:100%;border-collapse:collapse;}
.tbl th,.tbl td{text-align:left;border-bottom:1px solid #edf2f8;padding:8px 10px;font-size:.8rem;color:#233247;vertical-align:top;}
.tbl th{background:#f8fbff;font-family:"Space Grotesk","Trebuchet MS",sans-serif;letter-spacing:.06em;text-transform:uppercase;color:#456077;font-size:.7rem;}
.tbl tr:last-child td{border-bottom:0;}
/* Stat cards */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:10px;}
.stat-card{border:1px solid var(--line);border-radius:10px;background:#fff;padding:10px;}
.stat-label{margin:0 0 5px;font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-family:"Space Grotesk","Trebuchet MS",sans-serif;}
.stat-value{margin:0;font-size:1.2rem;font-weight:800;color:#173754;}
/* Filter bar */
.filter-bar{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;margin-bottom:12px;}
.filter-bar select,.filter-bar input{border:1px solid #c8d3df;border-radius:8px;padding:7px 9px;font-size:.84rem;font-family:"Outfit","Trebuchet MS",sans-serif;background:#fff;color:#1d3045;}
.filter-bar button{border:0;border-radius:8px;background:#155f83;color:#fff;padding:8px 12px;font-size:.84rem;font-weight:700;cursor:pointer;}
/* Buttons */
.btn-primary{border:0;border-radius:8px;background:#155f83;color:#fff;padding:8px 14px;font-size:.84rem;font-weight:700;cursor:pointer;}
.btn-warn{border:0;border-radius:8px;background:#8a6010;color:#fff;padding:7px 12px;font-size:.8rem;font-weight:700;cursor:pointer;}
.btn-danger{border:0;border-radius:8px;background:#8a1f1f;color:#fff;padding:7px 12px;font-size:.8rem;font-weight:700;cursor:pointer;}
.btn-ok{border:0;border-radius:8px;background:#1a6e3a;color:#fff;padding:7px 12px;font-size:.8rem;font-weight:700;cursor:pointer;}
/* Alert banner */
.alert-banner{border-radius:10px;padding:10px 14px;font-size:.86rem;margin-bottom:10px;}
.alert-banner.warn{background:var(--warn-bg);border:1px solid #f5c870;color:var(--warn);}
.alert-banner.err{background:var(--err-bg);border:1px solid #f0b7b3;color:var(--err);}
.alert-banner.ok{background:var(--ok-bg);border:1px solid #a0ddb5;color:var(--ok);}
/* Internal label */
.internal-label{font-size:.7rem;font-weight:700;letter-spacing:.08em;background:#fff3cd;border:1px solid #f0c040;border-radius:4px;padding:1px 5px;color:#6b4a00;text-transform:uppercase;}
</style>
</head>
<body>
<div class="page">
  <div class="hero">
    <div class="hero-top">
      <div>
        <p class="eyebrow" style="font-family:'Space Grotesk',sans-serif;font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:#d7f2f5;margin:0 0 8px;">Admin Portal · Finance</p>
        <h1>{{ $pageTitle ?? 'Finance' }}</h1>
        @isset($pageSubtitle)<p>{{ $pageSubtitle }}</p>@endisset
      </div>
      <div>
        <a href="/portal/admin" style="color:#ecfbff;text-decoration:none;border:1px solid #b8dfe4;border-radius:9px;padding:7px 11px;font-size:.8rem;background:rgba(11,49,75,.32);">← Admin Home</a>
      </div>
    </div>
    <nav class="hero-nav">
      <a href="/portal/admin/finance/ledger"     class="{{ ($activeNav??'') === 'ledger'    ? 'active' : '' }}">Ledger</a>
      <a href="/portal/admin/finance/payouts"    class="{{ ($activeNav??'') === 'payouts'   ? 'active' : '' }}">Payouts</a>
      <a href="/portal/admin/finance/refunds"    class="{{ ($activeNav??'') === 'refunds'   ? 'active' : '' }}">Refunds</a>
      <a href="/portal/admin/finance/disputes"   class="{{ ($activeNav??'') === 'disputes'  ? 'active' : '' }}">Disputes</a>
    </nav>
  </div>
  <div class="shell">
