@extends('layouts.admin',['title'=>'Akun pengelola'])
@section('content')
<div class="ad-page-head">
<div class="ad-page-title"><span class="ad-page-icon"><x-icon name="users"/></span><div><span class="ad-kicker">Level akses</span><h1>Akun pengelola</h1><p>{{ $users->total() }} akun</p></div></div>
<a class="ad-btn ad-btn-primary" href="{{ route('admin.users.create') }}"><x-icon name="plus"/> Tambah akun</a>
</div>
<div class="ad-levels">
<div><span class="ad-level is-super">Super admin</span><p>Mengelola seluruh website dan akun.</p></div>
<div><span class="ad-level is-faculty">Admin fakultas</span><p>Halaman fakultas, prodi di bawahnya, dan pengumuman unit.</p></div>
<div><span class="ad-level is-program">Admin program studi</span><p>Halaman dan pengumuman program studinya.</p></div>
</div>
<section class="ad-card ad-card-flush"><div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Nama</th><th>Level</th><th>Unit</th><th class="ad-right">Aksi</th></tr></thead><tbody>
@forelse($users as $account)
<tr><td><div class="ad-cell-title"><span class="ad-avatar">{{ mb_strtoupper(mb_substr($account->name,0,1)) }}</span><div><strong>{{ $account->name }}@if($account->is(auth()->user())) <span class="ad-pill">Anda</span>@endif</strong><small>{{ $account->email }}</small></div></div></td>
<td><span class="ad-level is-{{ $account->roleKey() ?? 'none' }}">{{ \App\Models\User::ROLES[$account->roleKey()] ?? 'Tanpa akses' }}</span></td>
<td>{{ $account->unit?->title ?? '—' }}</td>
<td><div class="ad-actions"><a class="ad-icon-btn is-edit" href="{{ route('admin.users.edit',$account) }}" title="Edit" aria-label="Edit {{ $account->name }}"><x-icon name="pencil"/></a>@unless($account->is(auth()->user()))<form method="post" action="{{ route('admin.users.destroy',$account) }}" data-confirm="Hapus akun ini?">@csrf @method('DELETE')<button class="ad-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $account->name }}"><x-icon name="trash"/></button></form>@endunless</div></td></tr>
@empty<tr><td colspan="4"><div class="ad-empty"><x-icon name="users"/><strong>Belum ada akun</strong></div></td></tr>@endforelse
</tbody></table></div></section>
{{ $users->links('partials.pagination') }}
@endsection
