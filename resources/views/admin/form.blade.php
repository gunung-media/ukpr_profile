@extends('layouts.admin',['title'=>($content->exists ? 'Edit ' : 'Tambah ').mb_strtolower(\App\Models\Content::KINDS[$kind])])
@section('content')
@php($label=mb_strtolower(\App\Models\Content::KINDS[$kind]))
<div class="ad-page-head">
<div class="ad-page-title"><span class="ad-page-icon"><x-icon :name="\App\Models\Content::KIND_ICONS[$kind]"/></span><div><a class="ad-back" href="{{ route('admin.index',$kind) }}"><x-icon name="chevron-left"/> {{ \App\Models\Content::KINDS[$kind] }}</a><h1>{{ $content->exists ? 'Edit' : 'Tambah' }} {{ $label }}</h1>@if($content->exists)<p>Terakhir diperbarui {{ $content->updated_at?->locale('id')->diffForHumans() }}</p>@endif</div></div>
@if($content->exists && (isset(array_flip(\App\Models\Content::SECTIONS)[$kind]) || $kind==='pages'))<a class="ad-btn ad-btn-light" href="{{ $content->publicUrl() }}" target="_blank" rel="noopener"><x-icon name="eye"/> Lihat di website</a>@endif
</div>
<form class="ad-form" method="post" enctype="multipart/form-data" action="{{ $content->exists ? route('admin.update',[$kind,$content]) : route('admin.store',$kind) }}">@csrf @if($content->exists)@method('PUT')@endif
<div class="ad-form-grid">
<div class="ad-form-main">
<section class="ad-card"><div class="ad-card-head"><h2>Informasi utama</h2></div>
<div class="ad-field"><label for="title">{{ $kind==='settings' ? 'Nama pengaturan' : 'Judul / nama' }} <em>*</em></label><input id="title" name="title" required maxlength="255" value="{{ old('title',$content->title) }}" placeholder="Tulis judul yang jelas"></div>
@if($locked)<input type="hidden" name="slug" value="{{ $content->slug }}"><div class="ad-note"><x-icon name="lock"/><span>Alamat halaman: <strong>{{ $content->publicUrl() }}</strong></span></div>
@else<div class="ad-field"><label for="slug">{{ $kind==='settings' ? 'Kunci pengaturan' : 'Slug (alamat URL)' }} <em>*</em></label><div class="ad-input-prefix"><span>{{ $kind==='settings' ? 'kunci' : '/' }}</span><input id="slug" name="slug" required maxlength="160" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug',$content->slug) }}"></div><small>Huruf kecil, angka, dan tanda hubung. Terisi otomatis dari judul.@if($kind==='settings') Pertahankan kunci yang sudah tersedia.@endif</small></div>@endif
<div class="ad-row">
@if($kind==='quicklinks')<div class="ad-field"><label for="subtitle">Ikon <em>*</em></label><select id="subtitle" name="subtitle" required>@foreach(\App\Models\Content::ICONS as $icon=>$name)<option value="{{ $icon }}" @selected(old('subtitle',$content->subtitle)===$icon)>{{ $name }} ({{ $icon }})</option>@endforeach</select></div>
@elseif($kind!=='settings')<div class="ad-field"><label for="subtitle">{{ ['leaders'=>'Jabatan','events'=>'Lokasi','faculties'=>'Program studi (ringkas)','programs'=>'Jenjang / subjudul'][$kind] ?? 'Subjudul' }}</label><input id="subtitle" name="subtitle" maxlength="255" value="{{ old('subtitle',$content->subtitle) }}"></div>@endif
</div>
@if($kind!=='settings')<div class="ad-field"><label for="summary">{{ $kind==='quicklinks' ? 'Deskripsi singkat' : 'Ringkasan' }}</label><textarea id="summary" name="summary" rows="3" maxlength="2000" placeholder="Satu-dua kalimat yang muncul di kartu dan pratinjau">{{ old('summary',$content->summary) }}</textarea></div>@endif
@if($kind!=='quicklinks')<div class="ad-field"><label for="body">{{ $kind==='settings' ? 'Nilai pengaturan' : 'Isi (Markdown)' }}</label><textarea id="body" class="{{ $kind==='settings' ? '' : 'ad-editor' }}" name="body" rows="{{ $kind==='settings' ? 5 : 16 }}" maxlength="100000">{{ old('body',$content->body) }}</textarea>@if($kind!=='settings')<small>Gunakan <code>## Judul</code>, <code>- daftar</code>, dan <code>**tebal**</code>. HTML mentah akan dihapus saat ditampilkan.</small>@endif</div>@endif
</section>
@if($kind!=='settings')
<section class="ad-card"><div class="ad-card-head"><h2>Tautan & SEO</h2></div>
<div class="ad-field"><label for="link">Tautan{!! $kind==='quicklinks' ? ' <em>*</em>' : '' !!}</label><div class="ad-input-prefix"><span><x-icon name="link"/></span><input id="link" name="link" maxlength="1000" value="{{ old('link',$content->link) }}" placeholder="/pmb atau https://…" @required($kind==='quicklinks')></div></div>
<div class="ad-field"><label for="seo_description">Deskripsi SEO</label><textarea id="seo_description" name="seo_description" rows="2" maxlength="300" placeholder="Ringkasan untuk mesin pencari (maks. 300 karakter)">{{ old('seo_description',$content->seo_description) }}</textarea></div>
</section>
@else<input type="hidden" name="position" value="{{ $content->position ?? 0 }}">@endif
</div>
<aside class="ad-form-side">
<section class="ad-card ad-sticky"><div class="ad-card-head"><h2>Publikasi</h2></div>
@if($locked)<input type="hidden" name="is_published" value="{{ (int)$content->is_published }}"><input type="hidden" name="position" value="{{ $content->position }}">@if($content->parent_id)<input type="hidden" name="parent_id" value="{{ $content->parent_id }}">@endif
<div class="ad-note"><x-icon name="lock"/><span>Status <span class="ad-pill {{ $content->is_published ? 'is-live' : '' }}">{{ $content->is_published ? 'Publik' : 'Draft' }}</span>, urutan, dan fakultas induk diatur oleh super admin.</span></div>
@else
<div class="ad-field"><label for="is_published">Status <em>*</em></label><select id="is_published" name="is_published"><option value="1" @selected(old('is_published',$content->is_published))>Publik</option><option value="0" @selected(!old('is_published',$content->is_published))>Draft</option></select></div>
@if($kind!=='settings')<div class="ad-field"><label for="position">Urutan <em>*</em></label><input id="position" type="number" min="0" max="100000" name="position" value="{{ old('position',$content->position??0) }}" required><small>Angka kecil tampil lebih dulu.</small></div>@endif
<div class="ad-field"><label for="published_at">Tanggal publikasi (WIB)</label><input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at',$content->published_at?->format('Y-m-d\TH:i')) }}"><small>Kosongkan untuk langsung tayang.</small></div>
@if($kind==='announcements')<div class="ad-field"><label for="unit_id">Ditampilkan di halaman {!! auth()->user()->is_admin ? '' : '<em>*</em>' !!}</label><select id="unit_id" name="unit_id" @required(!auth()->user()->is_admin)>@if(auth()->user()->is_admin)<option value="">Seluruh kampus (beranda)</option>@endif @foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string)old('unit_id',$content->unit_id ?? ($units->count()===1 ? $unit->id : ''))===(string)$unit->id)>{{ $unit->kind==='faculties' ? 'Fakultas' : 'Prodi' }} — {{ $unit->title }}</option>@endforeach</select><small>Pengumuman tampil di halaman fakultas/program studi yang dipilih.</small></div>@endif
@if(in_array($kind,['programs','photos','menus']))<div class="ad-field"><label for="parent_id">{{ $kind==='programs' ? 'Fakultas' : ($kind==='photos' ? 'Album' : 'Menu induk') }}{!! $kind!=='menus' ? ' <em>*</em>' : '' !!}</label><select id="parent_id" name="parent_id" @required($kind!=='menus')><option value="">{{ $kind==='menus' ? 'Tanpa induk (menu utama)' : 'Pilih induk' }}</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((string)old('parent_id',$content->parent_id)===(string)$parent->id)>{{ $parent->title }}</option>@endforeach</select></div>@endif
@endif
@if($kind==='events')<div class="ad-field"><label for="starts_at">Mulai (WIB)</label><input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at',$content->starts_at?->format('Y-m-d\TH:i')) }}"></div><div class="ad-field"><label for="ends_at">Selesai (WIB)</label><input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at',$content->ends_at?->format('Y-m-d\TH:i')) }}"></div>@endif
<button class="ad-btn ad-btn-primary ad-btn-block" type="submit"><x-icon name="check-circle"/> Simpan {{ $label }}</button>
<a class="ad-btn ad-btn-light ad-btn-block" href="{{ route('admin.index',$kind) }}">Batal</a>
</section>
@if(!in_array($kind,['settings','menus','quicklinks']))
<section class="ad-card"><div class="ad-card-head"><h2>Gambar</h2></div>
<label class="ad-drop" for="image">@if($content->image_path)<img class="ad-drop-preview" src="{{ route('media',$content) }}" alt="Gambar saat ini">@else<img class="ad-drop-preview" alt="" hidden>@endif<span class="ad-drop-hint"><x-icon name="upload"/><strong>Pilih gambar</strong><small>JPG, PNG, WebP · maks. 5 MB</small></span></label>
<input id="image" class="ad-file" type="file" name="image" accept="image/jpeg,image/png,image/webp">
@if($content->image_path)<label class="ad-check"><input type="checkbox" name="remove_image" value="1"> Hapus gambar saat ini</label>@endif
</section>
@endif
</aside>
</div>
</form>
@endsection
