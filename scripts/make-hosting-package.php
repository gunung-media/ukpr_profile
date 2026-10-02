<?php
// Local-only packaging. Folder names are variables; no existing deployment is touched.
$options=getopt('',['app-folder:','public-subdir:','prepared-dir:']);
$appFolder=$options['app-folder']??'ukpr_profile_app';$publicSubdir=$options['public-subdir']??'ukpr';
foreach([$appFolder,$publicSubdir] as $folder)if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,63}$/D',$folder))throw new RuntimeException('Nama folder harus unik, 3–64 karakter huruf/angka/_/-.');
if(in_array(strtolower($appFolder),['public_html','www','mail','etc','tmp','logs'],true))throw new RuntimeException('Nama folder aplikasi tidak aman.');
$root=realpath(__DIR__.'/..');
$prepared=realpath($options['prepared-dir']??(is_file($root.'/.runtime/prepared-path.txt')?trim(file_get_contents($root.'/.runtime/prepared-path.txt')):''));
if(!$prepared || !is_dir($prepared.'/vendor/laravel/framework') || is_dir($prepared.'/vendor/phpunit'))throw new RuntimeException('Siapkan folder staging dengan Composer --no-dev terlebih dahulu.');
if(!is_file($root.'/public/build/manifest.json') || !is_file($root.'/dist/ukpr.sql'))throw new RuntimeException('Build dan SQL wajib tersedia.');
$stage=$root.'/.runtime/package-'.bin2hex(random_bytes(4));$app=$stage.'/'.$appFolder;$public=$stage.'/public_html/'.$publicSubdir;
function directory(string $dir):void{if(!is_dir($dir))mkdir($dir,0755,true);}
function tree(string $source,string $target):void{
    directory($target);foreach(new DirectoryIterator($source) as $file){if($file->isDot()||$file->isLink())continue;$destination=$target.'/'.$file->getFilename();if($file->isDir())tree($file->getPathname(),$destination);else copy($file->getPathname(),$destination);}
}
directory($app);directory($public);tree($prepared,$app);
tree($root.'/public/build',$public.'/build');tree($root.'/public/fonts',$public.'/fonts');copy($root.'/public/favicon.svg',$public.'/favicon.svg');
$index=<<<'PHP'
<?php
use Illuminate\Http\Request;
define('LARAVEL_START',microtime(true));
$appPath=require __DIR__.'/app-path.php';
if(!is_file($appPath.'/vendor/autoload.php')){http_response_code(503);exit('Path aplikasi belum dikonfigurasi. Periksa app-path.php.');}
if(is_file($maintenance=$appPath.'/storage/framework/maintenance.php'))require $maintenance;
require $appPath.'/vendor/autoload.php';
$app=require $appPath.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
PHP;
file_put_contents($public.'/index.php',$index."\n");
file_put_contents($public.'/app-path.php',"<?php\n// Relative to /home/ACCOUNT/public_html/SUBDOMAIN. Edit if cPanel uses another document root.\nreturn dirname(__DIR__,2).'/".$appFolder."';\n");
$htaccess=<<<'HTACCESS'
Options -Indexes -MultiViews
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
<FilesMatch "^(app-path\.php|\.env.*|composer\.(json|lock)|.*\.sql)$">
    Require all denied
</FilesMatch>
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
</IfModule>
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json application/xml
</IfModule>
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
</IfModule>
HTACCESS;
file_put_contents($public.'/.htaccess',$htaccess."\n");
directory($stage.'/installation');copy($root.'/dist/ukpr.sql',$stage.'/installation/ukpr.sql');
foreach(['CPANEL.md','CONTENT-SOURCES.md','TEST-REPORT.md'] as $file)copy($root.'/docs/'.$file,$stage.'/installation/'.$file);
copy($root.'/scripts/installation-values.php',$stage.'/installation/installation-values.php');
file_put_contents($stage.'/MULAI-DI-SINI.txt',"UKPR — paket cPanel\n\n1. Baca installation/CPANEL.md.\n2. Folder aplikasi: $appFolder (di luar public_html).\n3. Document root: public_html/$publicSubdir.\n4. Import installation/ukpr.sql ke database BARU dan KOSONG.\n5. Salin .env.example ke .env; isi database, URL, APP_KEY dan SETUP_TOKEN.\n6. Aktifkan admin melalui https://SUBDOMAIN/setup; hapus SETUP_TOKEN setelahnya.\n\nTidak perlu Composer/npm/Node di hosting; vendor dan build sudah tersedia.\n");
$metadata=['app_folder'=>$appFolder,'public_subdir'=>$publicSubdir,'laravel'=>'13.34.0','php_minimum'=>'8.3','database'=>'MySQL/MariaDB','accounts_in_sql'=>0,'storage_symlink'=>false];
file_put_contents($stage.'/installation/package.json',json_encode($metadata,JSON_PRETTY_PRINT));
$zipPath=$root.'/dist/UKPR-cPanel-'.$publicSubdir.'.zip';$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Tidak dapat membuat ZIP.');
$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
foreach($files as $file){$relative=str_replace('\\','/',substr($file->getPathname(),strlen($stage)+1));if($file->isDir())$zip->addEmptyDir($relative);else $zip->addFile($file->getPathname(),$relative);}
$zip->close();file_put_contents($zipPath.'.sha256',hash_file('sha256',$zipPath).'  '.basename($zipPath)."\n");
file_put_contents($root.'/.runtime/package-path.txt',$stage);echo basename($zipPath).' — '.round(filesize($zipPath)/1048576,2).' MB'.PHP_EOL;
