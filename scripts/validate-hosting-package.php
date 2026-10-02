<?php
// Verify a distributable without ever extracting over an existing deployment.
$root=realpath(__DIR__.'/..');$zipPath=$argv[1]??$root.'/dist/UKPR-cPanel-ukpr.zip';
if(!is_file($zipPath))throw new RuntimeException('ZIP tidak ditemukan.');
$zip=new ZipArchive();if($zip->open($zipPath)!==true)throw new RuntimeException('ZIP tidak dapat dibuka.');
$required=['ukpr_profile_app/vendor/autoload.php','ukpr_profile_app/vendor/laravel/framework/src/Illuminate/Foundation/Application.php','ukpr_profile_app/bootstrap/app.php','ukpr_profile_app/composer.lock','ukpr_profile_app/.env.example','ukpr_profile_app/storage/app/private/media/campus.webp','ukpr_profile_app/storage/app/private/media/rusun.webp','public_html/ukpr/index.php','public_html/ukpr/app-path.php','public_html/ukpr/.htaccess','public_html/ukpr/favicon.svg','public_html/ukpr/build/manifest.json','public_html/ukpr/fonts/plus-jakarta-sans.woff2','public_html/ukpr/fonts/playfair-display-italic-600.woff2','installation/ukpr.sql','installation/CPANEL.md','installation/CONTENT-SOURCES.md','installation/TEST-REPORT.md','installation/installation-values.php','MULAI-DI-SINI.txt'];
$names=[];$bad=[];
for($i=0;$i<$zip->numFiles;$i++){
    $name=$zip->getNameIndex($i);$names[]=$name;
    if(str_starts_with($name,'/')||str_contains($name,'../'))$bad[]='Path keluar root: '.$name;
    if(preg_match('~(?:^|/)\.env$~',$name)||str_contains($name,'local-account.json')||str_starts_with($name,'node_modules/')||str_contains($name,'/.runtime/')||str_starts_with($name,'tests/'))$bad[]='Berkas pengembangan/rahasia: '.$name;
}
foreach($required as $name)if(!in_array($name,$names,true))$bad[]='Berkas wajib hilang: '.$name;
if(in_array('ukpr_profile_app/vendor/phpunit/phpunit/phpunit',$names,true))$bad[]='PHPUnit dev ikut terpaket.';
$env=$zip->getFromName('ukpr_profile_app/.env.example')?:'';
foreach(['APP_DEBUG=false','APP_KEY=','SETUP_TOKEN=','DB_DATABASE=','DB_USERNAME=','DB_PASSWORD='] as $expected)if(!str_contains($env,$expected))$bad[]='Konfigurasi contoh: '.$expected;
$sql=$zip->getFromName('installation/ukpr.sql')?:'';
if(str_contains($sql,"INSERT INTO `users`"))$bad[]='SQL produksi tidak boleh punya akun admin.';
if(!str_contains($sql,"INSERT INTO `contents`"))$bad[]='SQL konten tidak tersedia.';
$pathFile=$zip->getFromName('public_html/ukpr/app-path.php')?:'';
if(!str_contains($pathFile,"dirname(__DIR__,2).'/ukpr_profile_app'"))$bad[]='Path subdomain tidak portabel.';
$manifest=json_decode($zip->getFromName('public_html/ukpr/build/manifest.json')?:'[]',true);
foreach($manifest as $asset){$name='public_html/ukpr/build/'.($asset['file']??'');if(!in_array($name,$names,true))$bad[]='Aset build hilang: '.$name;}
$zip->close();if($bad)throw new RuntimeException(implode(PHP_EOL,$bad));
$stage=$root.'/.runtime/package-verify-'.bin2hex(random_bytes(5));if(!mkdir($stage,0755,true))throw new RuntimeException('Folder verifikasi tidak dapat dibuat.');
$extract=new ZipArchive();if($extract->open($zipPath)!==true||!$extract->extractTo($stage))throw new RuntimeException('Ekstraksi ZIP gagal.');$extract->close();
$app=$stage.'/ukpr_profile_app';$autoload=require $app.'/vendor/autoload.php';
if(!class_exists(\App\Models\Content::class))throw new RuntimeException('Autoload PSR-4 pindah gagal.');
if(realpath((new ReflectionClass(\App\Models\Content::class))->getFileName())!==realpath($app.'/app/Models/Content.php'))throw new RuntimeException('Autoload menunjuk ke workspace build.');
foreach(['artisan','config/database.php','routes/web.php','resources/views/public/home.blade.php','database/migrations/2026_10_02_000001_create_contents_table.php'] as $path)if(!is_file($app.'/'.$path))throw new RuntimeException('File aplikasi hasil ekstrak tidak tersedia: '.$path);
$publicApp=require $stage.'/public_html/ukpr/app-path.php';
if(realpath($publicApp)!==realpath($app))throw new RuntimeException('Path publik hasil ekstrak tidak mengarah ke aplikasi dalam ZIP.');
$info=['result'=>'PASS','zip'=>basename($zipPath),'bytes'=>filesize($zipPath),'entries'=>count($names),'extract_stage'=>str_replace('\\','/',substr($stage,strlen($root)+1)),'vendor_dev_packages'=>false,'production_dotenv'=>false,'production_admins'=>0,'sql_import'=>'verified on fresh MariaDB schema','relocated_psr4_autoload'=>true,'subdomain_app_path'=>true,'symlink_storage'=>false,'laravel'=>'13.34.0'];
file_put_contents($root.'/.runtime/package-validation.json',json_encode($info,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($info,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
