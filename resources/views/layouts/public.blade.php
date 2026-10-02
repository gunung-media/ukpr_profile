<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ isset($title) ? $title.' — ' : '' }}{{ $settings['site-name'] ?? 'Universitas Kristen Palangka Raya' }}</title>
<meta name="description" content="{{ $item->seo_description ?? $item->summary ?? $settings['site-description'] ?? 'Temukan pilihan studi dan informasi Universitas Kristen Palangka Raya.' }}">
<link rel="canonical" href="{{ url()->current() }}"><meta name="theme-color" content="#0047a1">
<meta property="og:title" content="{{ $title ?? $settings['site-name'] ?? 'UKPR' }}"><meta property="og:description" content="{{ $item->summary ?? $settings['site-description'] ?? '' }}"><meta property="og:url" content="{{ url()->current() }}"><meta property="og:type" content="{{ isset($item) && $item->kind==='news' ? 'article' : 'website' }}">
@if(isset($item) && $item->image_path)<meta property="og:image" content="{{ route('media',$item) }}">@endif
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="preload" href="{{ asset('fonts/plus-jakarta-sans.woff2') }}" as="font" type="font/woff2" crossorigin>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="um">
<a class="skip" href="#main">Lewati ke konten</a>
<div class="um-topbar"><div class="um-container um-topbar-inner">
<div class="um-topbar-info">
<div class="um-info"><x-icon name="map-pin"/><div><strong>ALAMAT</strong><span>{{ $settings['topline'] ?? 'Palangka Raya, Kalimantan Tengah' }}</span></div></div>
<span class="um-vr"></span>
<div class="um-info"><x-icon name="phone"/><div><strong>KONTAK</strong><span>{{ $settings['contact-phone'] ?? 'Hubungi kami' }}</span></div></div>
</div>
<div class="um-topbar-right">
<div class="um-motto"><span class="um-motto-title">Ilmu · Iman · Integritas</span><span class="um-motto-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span></div>
<a class="um-btn um-btn-orange" href="{{ url('/pmb') }}"><x-icon name="pencil"/> PMB UKPR</a>
</div>
</div></div>
<header class="um-header"><div class="um-container um-nav">
<a class="um-brand" href="{{ url('/') }}" aria-label="UKPR Beranda"><span class="um-logo">UKPR</span><span class="um-brand-text"><strong>Universitas Kristen</strong><span>Palangka Raya</span></span></a>
<nav id="navigation" class="um-menu" aria-label="Navigasi utama">
<ul>
<li><a class="um-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Beranda</a></li>
@foreach($menus as $menu)
@php($subs=$menu->children()->published()->get())
@if($subs->count())
<li class="um-has-drop"><button class="um-link" type="button" aria-expanded="false">{{ $menu->title }} <x-icon name="chevron-down"/></button>
<div class="um-drop"><a href="{{ $menu->link ?: url('/') }}">{{ $menu->title }}</a>@foreach($subs as $sub)<a href="{{ $sub->link ?: url('/') }}">{{ $sub->title }}</a>@endforeach</div></li>
@else<li><a class="um-link {{ request()->getPathInfo()===$menu->link ? 'active' : '' }}" href="{{ $menu->link ?: url('/') }}">{{ $menu->title }}</a></li>@endif
@endforeach
</ul>
<a class="um-btn um-btn-yellow um-menu-cta" href="{{ url('/pmb') }}">DAFTAR SEKARANG</a>
</nav>
<div class="um-nav-tools">
<a class="um-icon-btn" href="{{ url('/berita') }}" aria-label="Cari berita"><x-icon name="search"/></a>
<a class="um-btn um-btn-yellow um-nav-cta" href="{{ url('/pmb') }}">DAFTAR</a>
<button class="um-burger nav-toggle" type="button" aria-controls="navigation" aria-expanded="false" aria-label="Buka menu"><span></span><span></span><span></span></button>
</div>
</div></header>
<main id="main">@yield('content')</main>
<section class="um-cta"><div class="um-cta-shape" aria-hidden="true"></div><div class="um-container um-cta-inner" data-reveal>
<div><h2>{{ $settings['cta-title'] ?? 'Siap menjadi bagian dari keluarga besar UKPR?' }}</h2><p>{{ $settings['cta-text'] ?? 'Daftar sekarang dan jadikan UKPR tempat bertumbuhmu.' }}</p>
<div class="um-actions"><a class="um-btn um-btn-yellow um-btn-lg" href="{{ url('/pmb') }}">DAFTAR SEKARANG</a><a class="um-btn um-btn-green um-btn-lg" href="{{ url('/kontak') }}"><x-icon name="phone"/> HUBUNGI ADMIN PMB</a></div></div>
<div class="um-cta-badge" aria-hidden="true"><x-icon name="graduation-cap"/></div>
</div></section>
<footer class="um-footer"><div class="um-container">
<div class="um-footer-grid">
<div><span class="um-footer-logo">UKPR</span><p class="um-footer-name">{{ $settings['site-name'] ?? 'Universitas Kristen Palangka Raya' }}</p><p class="um-footer-tag">Ilmu · Iman · Integritas</p>
<p class="um-footer-line"><x-icon name="map-pin"/> {{ $settings['address'] ?? 'Palangka Raya, Kalimantan Tengah' }}</p><p class="um-footer-line"><x-icon name="phone"/> {{ $settings['contact-phone'] ?? '-' }}</p></div>
<div><h3>TAUTAN CEPAT</h3><a href="{{ url('/profil') }}">Tentang UKPR</a><a href="{{ url('/fakultas') }}">Fakultas & Prodi</a><a href="{{ url('/pmb') }}">Penerimaan Mahasiswa Baru</a><a href="{{ url('/pimpinan') }}">Pimpinan</a><a href="{{ url('/kontak') }}">Kontak</a></div>
<div><h3>INFORMASI</h3><a href="{{ url('/berita') }}">Berita</a><a href="{{ url('/agenda') }}">Agenda</a><a href="{{ url('/pengumuman') }}">Pengumuman</a><a href="{{ url('/fasilitas') }}">Fasilitas</a><a href="{{ url('/galeri') }}">Galeri</a></div>
<div><h3>LOKASI KAMPUS</h3><a class="um-map" href="https://maps.google.com/?q=Universitas+Kristen+Palangka+Raya" target="_blank" rel="noopener"><x-icon name="globe" class="um-map-bg"/><span class="um-btn um-btn-yellow"><x-icon name="map-pin"/> Lihat Peta</span></a></div>
</div>
<div class="um-footer-bottom"><p>© {{ date('Y') }} {{ $settings['site-name'] ?? 'Universitas Kristen Palangka Raya' }}. All rights reserved.</p><div><a href="{{ url('/sitemap.xml') }}">Sitemap</a><a href="{{ url('/login') }}">Admin</a></div></div>
</div></footer>
<a href="#main" class="um-totop" aria-label="Kembali ke atas"><x-icon name="chevron-up"/></a>
</body></html>
