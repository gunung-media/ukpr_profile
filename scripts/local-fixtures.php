<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!app()->environment('local') || config('database.connections.mysql.database')!=='ukpr_local') { throw new RuntimeException('Fixtures hanya untuk database lokal UKPR.'); }
foreach(['campus'=>[['banners','perjalanan-ukpr'],['news','mahasiswa-kkn-2026'],['albums','kegiatan-mahasiswa']], 'rusun'=>[['news','rumah-susun-mahasiswa'],['facilities','rumah-susun']]] as $asset=>$records) {
    foreach($records as [$kind,$slug]) App\Models\Content::where('kind',$kind)->where('slug',$slug)->update(['image_path'=>'media/'.$asset.'.webp']);
}
$album=App\Models\Content::where('kind','albums')->first();
App\Models\Content::firstOrCreate(['kind'=>'photos','slug'=>'pelepasan-kkn'],['title'=>'Pelepasan mahasiswa KKN','parent_id'=>$album->id,'is_published'=>true,'image_path'=>'media/campus.webp']);
$email='admin@ukpr.test';$password='UKPR!'.bin2hex(random_bytes(8)).'aA';
$user=App\Models\User::firstOrNew(['email'=>$email]);$user->name='Admin Uji UKPR';$user->password=$password;$user->is_admin=true;$user->save();
$regular=App\Models\User::firstOrNew(['email'=>'member@ukpr.test']);$regular->name='Pengguna Uji';$regular->password=$password;$regular->is_admin=false;$regular->save();
file_put_contents(__DIR__.'/../.runtime/local-account.json',json_encode(compact('email','password'),JSON_PRETTY_PRINT));
echo "Akun lokal dibuat; kredensial hanya pada .runtime/local-account.json.\n";
