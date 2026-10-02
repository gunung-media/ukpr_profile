@extends('layouts.admin',['title'=>\App\Models\Content::KINDS[$kind]])
@section('content')
<div class="ad-page-head">
<div class="ad-page-title"><span class="ad-page-icon"><x-icon :name="\App\Models\Content::KIND_ICONS[$kind]"/></span><div><span class="ad-kicker">Konten website</span><h1>{{ \App\Models\Content::KINDS[$kind] }}</h1><p>{{ $items->total() }} entri</p></div></div>
@if($canCreate)<a class="ad-btn ad-btn-primary" href="{{ route('admin.create',$kind) }}"><x-icon name="plus"/> Tambah {{ mb_strtolower(\App\Models\Content::KINDS[$kind]) }}</a>@endif
</div>
<section class="ad-card ad-card-flush">
<form class="ad-toolbar" method="get"><label class="ad-search"><x-icon name="search"/><span class="sr-only">Cari konten</span><input id="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari judul…"></label><button class="ad-btn ad-btn-light">Cari</button>@if(request('q'))<a class="ad-link" href="{{ route('admin.index',$kind) }}">Reset</a>@endif</form>
<div class="ad-table-wrap"><table class="ad-table ad-table-content"><thead><tr><th>Judul</th><th>Status</th><th>Urutan</th><th>Diperbarui</th><th class="ad-right">Aksi</th></tr></thead><tbody>
@forelse($items as $entry)
<tr>
<td><div class="ad-cell-title">@if($entry->image_path)<img src="{{ route('media',$entry) }}" alt="" loading="lazy">@else<span class="ad-thumb"><x-icon :name="$kind==='quicklinks' && $entry->subtitle ? $entry->subtitle : \App\Models\Content::KIND_ICONS[$kind]"/></span>@endif<div><strong>{{ $entry->title }}</strong><small>{{ $entry->slug }}@if($kind==='announcements') · {{ $entry->unit?->title ?? 'Seluruh kampus' }}@endif</small></div></div></td>
<td><span class="ad-pill {{ $entry->is_published ? ($entry->published_at?->isFuture() ? 'is-scheduled' : 'is-live') : '' }}">{{ $entry->is_published ? ($entry->published_at?->isFuture() ? 'Terjadwal' : 'Publik') : 'Draft' }}</span></td>
<td>{{ $entry->position }}</td>
<td class="ad-muted">{{ $entry->updated_at?->locale('id')->translatedFormat('d M Y') }}</td>
<td><div class="ad-actions">
@if(isset(array_flip(\App\Models\Content::SECTIONS)[$kind]) || $kind==='pages')<a class="ad-icon-btn" href="{{ $entry->publicUrl() }}" target="_blank" rel="noopener" title="Lihat" aria-label="Lihat {{ $entry->title }}"><x-icon name="eye"/></a>@endif
<a class="ad-icon-btn is-edit" href="{{ route('admin.edit',[$kind,$entry]) }}" title="Edit" aria-label="Edit {{ $entry->title }}"><x-icon name="pencil"/></a>
@if($canCreate)<form method="post" action="{{ route('admin.destroy',[$kind,$entry]) }}" data-confirm="Hapus konten ini?">@csrf @method('DELETE')<button class="ad-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $entry->title }}"><x-icon name="trash"/></button></form>@endif
</div></td>
</tr>
@empty
<tr><td colspan="5"><div class="ad-empty"><x-icon :name="\App\Models\Content::KIND_ICONS[$kind]"/><strong>Belum ada konten</strong><p>{{ request('q') ? 'Tidak ada hasil untuk pencarian ini.' : 'Tambahkan konten pertama Anda.' }}</p></div></td></tr>
@endforelse
</tbody></table></div>
</section>
{{ $items->links('partials.pagination') }}
@endsection
