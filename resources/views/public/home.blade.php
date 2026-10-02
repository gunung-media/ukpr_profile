@extends('layouts.public')
@section('content')
@php($slides=$banners->filter(fn($b)=>$b->image_path)->values())
<section class="um-hero">
<div class="um-hero-slides" aria-hidden="true">@forelse($slides as $slide)<img class="{{ $loop->first ? 'is-active' : '' }}" src="{{ route('media',$slide) }}" alt="" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>@empty<div class="um-hero-fallback"></div>@endforelse</div>
<div class="um-hero-overlay"></div>
<div class="um-container um-hero-content" data-reveal>
<p class="um-welcome">Selamat datang di</p>
<h1><span>Universitas Kristen</span><span class="um-gradient-text">Palangka Raya</span></h1>
<div class="um-glass"><x-icon name="check-circle"/> {{ $settings['hero-note'] ?? 'Berakar di Kalimantan. Bertumbuh untuk Indonesia.' }}</div>
<p class="um-tagline">"{{ $hero->title ?? 'Ilmu yang menginspirasi. Karakter yang berarti.' }}"</p>
<div class="um-actions">
<a class="um-btn um-btn-yellow um-btn-xl um-pulse" href="{{ $hero?->link ?: url('/pmb') }}">DAFTAR SEKARANG <x-icon name="rocket"/></a>
<a class="um-btn um-btn-blue um-btn-xl" href="{{ url('/fakultas') }}">LIHAT PROGRAM STUDI</a>
<a class="um-btn um-btn-outline um-btn-xl" href="{{ url('/galeri') }}"><x-icon name="play-circle"/> JELAJAHI KAMPUS</a>
</div>
@if($slides->count()>1)<div class="um-hero-dots">@foreach($slides as $slide)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" aria-label="Slide {{ $loop->iteration }}"></button>@endforeach</div>@endif
</div>
</section>

@if($quicklinks->count())<section class="um-quick"><div class="um-container um-quick-grid" data-reveal>
@foreach($quicklinks as $quick)
@php($external=str_starts_with($quick->link,'http'))
<a class="um-qa" href="{{ $external ? $quick->link : url($quick->link) }}" @if($external) target="_blank" rel="noopener" @endif><span class="um-icon-box"><x-icon :name="$quick->subtitle ?: 'arrow-up-right'"/></span><h3>{{ mb_strtoupper($quick->title) }}</h3><p>{{ $quick->summary }}</p></a>
@endforeach
</div></section>@else<div class="um-quick-spacer"></div>@endif

<section class="um-stats"><div class="um-container um-stats-grid">
@foreach([['faculties','Fakultas'],['programs','Program Studi'],['facilities','Fasilitas Kampus'],['events','Agenda Kegiatan']] as [$key,$label])
@continue(!$stats[$key])
<div data-reveal><strong data-count="{{ $stats[$key] }}">{{ $stats[$key] }}</strong><span>{{ $label }}</span></div>
@endforeach
</div></section>

<section class="um-section um-white"><div class="um-container um-why">
<h2 class="um-title-bar um-title-lg" data-reveal>Mengapa<br>Memilih<br>UKPR?</h2>
<ul class="um-reasons" data-reveal>
@php($reasons=isset($settings['reasons']) ? array_filter(array_map('trim',explode("\n",$settings['reasons']))) : ['Pendidikan Bernilai Kristiani','Pilihan Studi Beragam','Dosen Berpengalaman','Lingkungan Kampus Nyaman','Hunian Rumah Susun','Pengabdian Masyarakat','Berakar di Kalimantan','Kegiatan Kemahasiswaan','Jejaring Alumni'])
@php($icons=['cross','book-open','users','sprout','home','heart','globe','award','shield-check'])
@foreach($reasons as $reason)<li><x-icon :name="$icons[$loop->index % count($icons)]"/><span>{{ $reason }}</span></li>@endforeach
</ul>
</div></section>

@if($intro)
<section class="um-section um-light"><div class="um-container um-intro" data-reveal>
<div><span class="um-kicker">Kenali UKPR</span><h2 class="um-h2">{{ $intro->title }}</h2></div>
<div><p class="um-lead">{{ $intro->summary }}</p><p>{{ $intro->body }}</p><a class="um-more" href="{{ url('/profil') }}">Lebih dekat dengan UKPR <x-icon name="arrow-right"/></a></div>
</div></section>
@endif

<section class="um-section um-white um-bt"><div class="um-container">
<div class="um-head" data-reveal><div><h2 class="um-h3">Fakultas</h2><p>{{ $settings['academic-intro'] ?? 'Pilihan fakultas untuk masa depanmu' }}</p></div>
<div class="um-head-tools"><a class="um-btn um-btn-ghost" href="{{ url('/fakultas') }}">Lihat Semua</a><button class="um-arrow" type="button" data-scroll="-1" data-target="faculty-track" aria-label="Sebelumnya"><x-icon name="chevron-left"/></button><button class="um-arrow" type="button" data-scroll="1" data-target="faculty-track" aria-label="Berikutnya"><x-icon name="chevron-right"/></button></div></div>
<div class="um-track" id="faculty-track" data-reveal>
@forelse($faculties as $faculty)
<article class="um-fac">@if($faculty->image_path)<img src="{{ route('media',$faculty) }}" alt="{{ $faculty->title }}" loading="lazy">@else<div class="um-fac-ph"><x-icon name="graduation-cap"/><span>{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span></div>@endif
<div class="um-fac-body"><h3>{{ $faculty->title }}</h3><p>{{ $faculty->subtitle }}</p><a class="um-btn um-btn-blue um-btn-block" href="{{ $faculty->publicUrl() }}">Lihat Fakultas</a></div></article>
@empty<p class="um-empty">Informasi fakultas sedang dilengkapi.</p>@endforelse
</div>
</div></section>

<section class="um-section um-light um-bt"><div class="um-container">
<div class="um-head" data-reveal><h2 class="um-h3 um-title-bar">Berita Terbaru</h2><a class="um-more" href="{{ url('/berita') }}">Lihat Semua <x-icon name="arrow-right"/></a></div>
@if($news->count())
<div class="um-news" data-reveal>
@php($lead=$news->first())
<a class="um-news-lead" href="{{ $lead->publicUrl() }}">@if($lead->image_path)<img src="{{ route('media',$lead) }}" alt="" loading="lazy">@endif<div class="um-news-lead-body"><span class="um-badge">{{ $lead->published_at?->locale('id')->translatedFormat('d M Y') ?? 'Berita' }}</span><h3>{{ $lead->title }}</h3><span class="um-read">Baca Selengkapnya <x-icon name="arrow-right"/></span></div></a>
<div class="um-news-list">@foreach($news->skip(1) as $n)<a class="um-news-item" href="{{ $n->publicUrl() }}">@if($n->image_path)<img src="{{ route('media',$n) }}" alt="" loading="lazy">@else<span class="um-news-thumb"><x-icon name="newspaper"/></span>@endif<div><small>{{ $n->published_at?->locale('id')->translatedFormat('d M Y') }}</small><h4>{{ $n->title }}</h4></div></a>@endforeach
<a class="um-news-item um-news-all" href="{{ url('/berita') }}"><span class="um-news-thumb"><x-icon name="arrow-up-right"/></span><div><small>Arsip</small><h4>Jelajahi semua berita UKPR</h4></div></a></div>
</div>
@else<p class="um-empty">Berita terbaru akan hadir di sini.</p>@endif
</div></section>

<section class="um-section um-blue"><div class="um-container um-updates">
<div data-reveal><div class="um-head"><h2 class="um-h3 um-title-bar">Pengumuman</h2><a class="um-btn um-btn-outline um-btn-sm" href="{{ url('/pengumuman') }}">Lihat Semua</a></div>
@forelse($announcements as $notice)<a class="um-notice" href="{{ $notice->publicUrl() }}"><span class="um-notice-icon"><x-icon name="megaphone"/></span><div><small>{{ $notice->published_at?->locale('id')->translatedFormat('d M Y') }}</small><h3>{{ $notice->title }}</h3></div><x-icon name="arrow-up-right"/></a>@empty<p class="um-empty um-empty-dark">Belum ada pengumuman.</p>@endforelse</div>
<div data-reveal><div class="um-head"><h2 class="um-h3 um-title-bar">Agenda</h2><a class="um-btn um-btn-outline um-btn-sm" href="{{ url('/agenda') }}">Lihat Semua</a></div>
@forelse($events as $event)<a class="um-notice" href="{{ $event->publicUrl() }}"><span class="um-date"><strong>{{ $event->starts_at?->format('d') ?? '—' }}</strong>{{ $event->starts_at?->locale('id')->translatedFormat('M') }}</span><div><small>{{ $event->subtitle }}</small><h3>{{ $event->title }}</h3></div><x-icon name="arrow-up-right"/></a>@empty<p class="um-empty um-empty-dark">Jadwal kegiatan berikutnya akan diumumkan di sini.</p>@endforelse</div>
</div></section>

<section class="um-section um-dark"><div class="um-container um-facility">
<div data-reveal><h2 class="um-h2 um-yellow">Fasilitas & Lingkungan Kampus</h2><p>{{ $settings['facility-intro'] ?? 'Universitas Kristen Palangka Raya menyediakan lingkungan belajar yang nyaman dan fasilitas pendukung untuk menunjang prestasi mahasiswa.' }}</p><a class="um-btn um-btn-yellow" href="{{ url('/fasilitas') }}">LIHAT FASILITAS <x-icon name="arrow-right"/></a></div>
<div class="um-facility-grid" data-reveal>
@forelse($facilities as $f)<a class="um-facility-card" href="{{ $f->publicUrl() }}">@if($f->image_path)<img src="{{ route('media',$f) }}" alt="" loading="lazy">@else<x-icon name="building"/>@endif<h3>{{ $f->title }}</h3></a>@empty
@foreach([['building','Gedung Kuliah'],['book-open','Perpustakaan'],['home','Rumah Susun']] as [$i,$t])<div class="um-facility-card"><x-icon :name="$i"/><h3>{{ $t }}</h3></div>@endforeach
@endforelse
</div>
</div></section>

@if($banners->count()>1)
<section class="um-section um-light"><div class="um-container">
<div class="um-head" data-reveal><div><span class="um-kicker">Sorotan Kampus</span><h2 class="um-h2">Terobosan <span class="um-yellow-dark">Nyata.</span></h2></div></div>
<div class="um-highlights" data-reveal>@foreach($banners->skip(1) as $banner)<a class="um-highlight" href="{{ $banner->link ?: url('/') }}">@if($banner->image_path)<img src="{{ route('media',$banner) }}" alt="" loading="lazy">@endif<div><span class="um-badge">Sorotan</span><h3>{{ $banner->title }}</h3><p>{{ $banner->summary }}</p></div></a>@endforeach</div>
</div></section>
@endif
@endsection
