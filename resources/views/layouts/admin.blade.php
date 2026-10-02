@php($me=auth()->user())
@php($groups=['Konten'=>['pages','news','announcements','events','banners','quicklinks'],'Akademik'=>['faculties','programs','leaders'],'Kampus'=>['facilities','albums','photos'],'Website'=>['menus','settings']])
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>{{ $title ?? 'Admin' }} — UKPR</title><link rel="icon" href="{{ asset('favicon.svg') }}">
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="ad">
<a class="skip" href="#main">Lewati ke konten</a>
<div class="ad-shell">
<aside class="ad-side" id="ad-side">
<a class="ad-brand" href="{{ route('admin.dashboard') }}"><span class="ad-logo">UKPR</span><span><strong>Panel Admin</strong><small>Universitas Kristen Palangka Raya</small></span></a>
<div class="ad-role"><span class="ad-role-tag">{{ \App\Models\User::ROLES[$me->roleKey()] ?? 'Pengelola' }}</span>@if($me->unit)<strong>{{ $me->unit->title }}</strong>@endif</div>
<nav class="ad-nav" aria-label="Menu admin">
<a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><x-icon name="grid"/> Ringkasan</a>
@foreach($groups as $group=>$kinds)
@php($visible=array_values(array_intersect($kinds,$me->allowedKinds())))
@if($visible)<span class="ad-nav-label">{{ $group }}</span>@foreach($visible as $key)<a class="{{ ($kind??'')===$key ? 'active' : '' }}" href="{{ route('admin.index',$key) }}"><x-icon :name="\App\Models\Content::KIND_ICONS[$key]"/> {{ \App\Models\Content::KINDS[$key] }}</a>@endforeach @endif
@endforeach
@if($me->is_admin)<span class="ad-nav-label">Sistem</span><a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><x-icon name="users"/> Akun pengelola</a>@endif
</nav>
<a class="ad-side-site" href="{{ url('/') }}" target="_blank" rel="noopener"><x-icon name="globe"/> Lihat website <x-icon name="arrow-up-right"/></a>
</aside>
<div class="ad-backdrop" data-ad-close></div>
<div class="ad-work">
<header class="ad-top">
<button class="ad-burger" type="button" aria-controls="ad-side" aria-expanded="false" aria-label="Buka menu"><x-icon name="menu"/></button>
<div class="ad-top-title"><small>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</small><strong>{{ $title ?? 'Panel Admin' }}</strong></div>
<div class="ad-top-right">
<a class="ad-top-link" href="{{ url('/') }}" target="_blank" rel="noopener"><x-icon name="eye"/><span>Website</span></a>
<div class="ad-user"><span class="ad-avatar">{{ mb_strtoupper(mb_substr($me->name,0,1)) }}</span><span class="ad-user-meta"><strong>{{ $me->name }}</strong><small>{{ $me->email }}</small></span></div>
<form method="post" action="{{ route('logout') }}">@csrf<button class="ad-icon-btn" type="submit" title="Keluar" aria-label="Keluar"><x-icon name="log-out"/></button></form>
</div>
</header>
<main id="main" class="ad-main">@include('partials.messages')@yield('content')</main>
<footer class="ad-foot">© {{ date('Y') }} UKPR · Panel pengelola website</footer>
</div>
</div>
</body>
</html>
