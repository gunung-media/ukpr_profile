@if(session('status'))<div class="alert success" role="status"><x-icon name="check-circle"/><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert error" role="alert"><x-icon name="shield-check"/><div><strong>Periksa kembali isian berikut:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
