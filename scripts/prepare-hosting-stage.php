<?php
// Isolated production build. Composer is run from this exact application root so PSR-4 paths relocate.
$root=realpath(__DIR__.'/..');$folder='ukpr_profile_app';
$stage=$root.'/.runtime/hosting-build-'.bin2hex(random_bytes(5));$app=$stage.'/'.$folder;
function ensureDirectory(string $path):void { if(!is_dir($path)&&!mkdir($path,0755,true)&&!is_dir($path))throw new RuntimeException("Tidak dapat membuat $path"); }
function copyTree(string $from,string $to):void {
    ensureDirectory($to);foreach(new DirectoryIterator($from) as $entry){if($entry->isDot()||$entry->isLink())continue;$dest=$to.'/'.$entry->getFilename();if($entry->isDir())copyTree($entry->getPathname(),$dest);else copy($entry->getPathname(),$dest);}
}
if(!is_file($root.'/dist/ukpr.sql')||!is_file($root.'/public/build/manifest.json'))throw new RuntimeException('Build produksi dan SQL wajib tersedia.');
ensureDirectory($app);
foreach(['app','config','routes','resources','database','bootstrap'] as $folder)copyTree($root.'/'.$folder,$app.'/'.$folder);
ensureDirectory($app.'/public');copy($root.'/public/favicon.svg',$app.'/public/favicon.svg');copyTree($root.'/public/build',$app.'/public/build');copyTree($root.'/public/fonts',$app.'/public/fonts');
foreach(['artisan','composer.json','composer.lock','.env.example'] as $file)copy($root.'/'.$file,$app.'/'.$file);
foreach(['storage/app/private/media','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','bootstrap/cache'] as $relative)ensureDirectory($app.'/'.$relative);
foreach(new DirectoryIterator($app.'/bootstrap/cache') as $cached)if(!$cached->isDot()&&$cached->isFile())unlink($cached->getPathname());
foreach(new DirectoryIterator($app.'/bootstrap/cache') as $cached)if(!$cached->isDot()&&$cached->isFile())unlink($cached->getPathname());
copyTree($root.'/storage/app/private/media',$app.'/storage/app/private/media');
foreach(new DirectoryIterator($app.'/storage/app/private/media') as $image)if(!$image->isDot()&&$image->isFile()&&!in_array($image->getFilename(),['campus.webp','rusun.webp'],true))unlink($image->getPathname());
$manifest=json_decode(file_get_contents($app.'/public/build/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as $entry)if(empty($entry['file'])||!is_file($app.'/public/build/'.$entry['file']))throw new RuntimeException('Aset manifest tidak lengkap.');
$composer=json_decode(file_get_contents($app.'/composer.json'),false,512,JSON_THROW_ON_ERROR);
$composer->config->{'optimize-autoloader'}=false;$composer->scripts=new stdClass;
file_put_contents($app.'/composer.json',json_encode($composer,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
file_put_contents($root.'/.runtime/prepared-path.txt',$app);echo $app.PHP_EOL;
