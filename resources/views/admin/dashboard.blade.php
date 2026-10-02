@extends('layouts.admin',['title'=>'Ringkasan'])
@section('content')
<section class="ad-hero">
<div><span class="ad-kicker">Ruang pengelola</span><h1>Selamat datang, {{ $user->name }}!</h1>
@if($user->is_admin)<p>Kelola seluruh konten, tampilan beranda, dan akun pengelola website UKPR dari satu tempat.</p>
@else<p>Anda mengelola <strong>{{ $user->unit->title }}</strong>. Perbarui halaman {{ $user->roleKey()==='faculty' ? 'fakultas dan program studi di bawahnya' : 'program studi' }} serta buat pengumuman yang tampil di halaman unit Anda.</p>@endif
<div class="ad-hero-actions">
@if($user->is_admin)<a class="ad-btn ad-btn-yellow" href="{{ route('admin.create','news') }}"><x-icon name="plus"/> Tulis berita</a><a class="ad-btn ad-btn-glass" href="{{ route('admin.create','announcements') }}"><x-icon name="megaphone"/> Buat pengumuman</a><a class="ad-btn ad-btn-glass" href="{{ route('admin.users.index') }}"><x-icon name="users"/> Akun pengelola</a>
@else<a class="ad-btn ad-btn-yellow" href="{{ route('admin.create','announcements') }}"><x-icon name="plus"/> Buat pengumuman</a><a class="ad-btn ad-btn-glass" href="{{ route('admin.edit',[$user->unit->kind,$user->unit]) }}"><x-icon name="pencil"/> Edit halaman {{ $user->unit->title }}</a><a class="ad-btn ad-btn-glass" href="{{ $user->unit->publicUrl() }}" target="_blank" rel="noopener"><x-icon name="eye"/> Lihat halaman publik</a>@endif
</div></div>
<div class="ad-hero-stat"><strong>{{ $counts->sum() }}</strong><span>Total konten</span><small>{{ $drafts }} masih draft</small></div>
</section>
<div class="ad-section-head"><h2>Konten Anda</h2><p>Pilih kategori untuk mengelola isinya.</p></div>
<div class="ad-stats">
@foreach($counts as $key=>$total)
<a class="ad-stat" href="{{ route('admin.index',$key) }}"><span class="ad-stat-icon"><x-icon :name="\App\Models\Content::KIND_ICONS[$key]"/></span><span class="ad-stat-body"><small>{{ \App\Models\Content::KINDS[$key] }}</small><strong>{{ $total }}</strong></span><x-icon name="arrow-right" class="icon ad-stat-go"/></a>
@endforeach
</div>
<div class="ad-grid-2">
<section class="ad-card"><div class="ad-card-head"><h2>Terakhir diperbarui</h2></div>
@forelse($recent as $entry)
<a class="ad-recent" href="{{ route('admin.edit',[$entry->kind,$entry]) }}"><span class="ad-recent-icon"><x-icon :name="\App\Models\Content::KIND_ICONS[$entry->kind]"/></span><span class="ad-recent-body"><strong>{{ $entry->title }}</strong><small>{{ \App\Models\Content::KINDS[$entry->kind] }} · {{ $entry->updated_at?->locale('id')->diffForHumans() }}</small></span><span class="ad-pill {{ $entry->is_published ? 'is-live' : '' }}">{{ $entry->is_published ? 'Publik' : 'Draft' }}</span></a>
@empty<p class="ad-muted">Belum ada konten.</p>@endforelse
</section>
<section class="ad-card ad-tips"><div class="ad-card-head"><h2>Panduan singkat</h2></div>
<ul>
<li><x-icon name="check-circle"/><span>Isi artikel memakai <strong>Markdown</strong>: <code>## Judul</code>, <code>- daftar</code>, <code>**tebal**</code>. HTML mentah otomatis dihapus.</span></li>
<li><x-icon name="check-circle"/><span>Pilih status <strong>Draft</strong> untuk menyimpan tanpa menampilkan ke publik.</span></li>
<li><x-icon name="check-circle"/><span>Atur <strong>tanggal publikasi</strong> di masa depan untuk menjadwalkan konten.</span></li>
@if($user->is_admin)<li><x-icon name="check-circle"/><span>Kartu di bawah hero beranda diatur lewat menu <strong>Akses cepat</strong>.</span></li><li><x-icon name="check-circle"/><span>Teks beranda, kontak, dan nama situs diatur lewat menu <strong>Pengaturan</strong>.</span></li>
@else<li><x-icon name="check-circle"/><span>Pengumuman Anda tampil di halaman unit dan daftar pengumuman kampus.</span></li>@endif
</ul></section>
</div>
@endsection
