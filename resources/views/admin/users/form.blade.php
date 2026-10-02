@extends('layouts.admin',['title'=>'Akun pengelola'])
@section('content')
<div class="ad-page-head"><div class="ad-page-title"><span class="ad-page-icon"><x-icon name="users"/></span><div><a class="ad-back" href="{{ route('admin.users.index') }}"><x-icon name="chevron-left"/> Akun pengelola</a><h1>{{ $account->exists ? 'Edit' : 'Tambah' }} akun pengelola</h1></div></div></div>
<form class="ad-form" method="post" action="{{ $account->exists ? route('admin.users.update',$account) : route('admin.users.store') }}">@csrf @if($account->exists)@method('PUT')@endif
<div class="ad-form-grid"><div class="ad-form-main">
<section class="ad-card"><div class="ad-card-head"><h2>Data akun</h2></div>
<div class="ad-row"><div class="ad-field"><label for="name">Nama <em>*</em></label><input id="name" name="name" required maxlength="100" value="{{ old('name',$account->name) }}"></div>
<div class="ad-field"><label for="email">Email <em>*</em></label><input id="email" type="email" name="email" required maxlength="255" autocomplete="off" value="{{ old('email',$account->email) }}"></div></div>
</section>
<section class="ad-card"><div class="ad-card-head"><h2>Password</h2>@if($account->exists)<small>Kosongkan jika tidak diubah</small>@endif</div>
<div class="ad-row"><div class="ad-field"><label for="password">Password {!! $account->exists ? '' : '<em>*</em>' !!}</label><input id="password" type="password" name="password" autocomplete="new-password" @required(!$account->exists)></div>
<div class="ad-field"><label for="password_confirmation">Ulangi password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div></div>
<small class="ad-muted">Minimal 12 karakter dengan huruf besar, huruf kecil, angka, dan simbol.</small>
</section></div>
<aside class="ad-form-side"><section class="ad-card ad-sticky"><div class="ad-card-head"><h2>Level &amp; unit</h2></div>
<div class="ad-field"><label for="role">Level akun <em>*</em></label><select id="role" name="role" required>@foreach(\App\Models\User::ROLES as $key=>$name)<option value="{{ $key }}" @selected(old('role',$account->roleKey() ?? 'faculty')===$key)>{{ $name }}</option>@endforeach</select></div>
<div class="ad-field"><label for="unit_id">Unit (fakultas / program studi)</label><select id="unit_id" name="unit_id"><option value="">— Tidak ada (super admin) —</option><optgroup label="Fakultas">@foreach($units->where('kind','faculties') as $unit)<option value="{{ $unit->id }}" @selected((string)old('unit_id',$account->unit_id)===(string)$unit->id)>{{ $unit->title }}</option>@endforeach</optgroup><optgroup label="Program studi">@foreach($units->where('kind','programs') as $unit)<option value="{{ $unit->id }}" @selected((string)old('unit_id',$account->unit_id)===(string)$unit->id)>{{ $unit->title }}{{ $unit->parent ? ' — '.$unit->parent->title : '' }}</option>@endforeach</optgroup></select><small>Admin fakultas: pilih fakultas. Admin program studi: pilih program studi.</small></div>
<button class="ad-btn ad-btn-primary ad-btn-block" type="submit"><x-icon name="check-circle"/> Simpan akun</button><a class="ad-btn ad-btn-light ad-btn-block" href="{{ route('admin.users.index') }}">Batal</a>
</section></aside></div></form>
@endsection
