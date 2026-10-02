import fs from 'node:fs';
import crypto from 'node:crypto';
const write=(p,s)=>fs.writeFileSync(p,s);
const composer=JSON.parse(fs.readFileSync('composer.json','utf8'));
composer.name='ukpr/profile';composer.description='Profil Universitas Kristen Palangka Raya';composer['minimum-stability']='stable';
composer.scripts['post-create-project-cmd']=['@php artisan key:generate --ansi'];delete composer.scripts.setup;delete composer.scripts.dev;
write('composer.json',JSON.stringify(composer,null,4)+'\n');
write('package.json',JSON.stringify({private:true,type:'module',scripts:{build:'vite build',dev:'vite'},devDependencies:{'laravel-vite-plugin':'^2.0.0',vite:'^7.0.7'}},null,2)+'\n');
const db=`<?php
return [
 'default'=>env('DB_CONNECTION','mysql'),
 'connections'=>[
  'mysql'=>['driver'=>'mysql','host'=>env('DB_HOST','127.0.0.1'),'port'=>env('DB_PORT','3306'),'database'=>env('DB_DATABASE','ukpr'),'username'=>env('DB_USERNAME'),'password'=>env('DB_PASSWORD'),'unix_socket'=>env('DB_SOCKET',''),'charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'','prefix_indexes'=>true,'strict'=>true,'engine'=>null],
  'mariadb'=>['driver'=>'mariadb','host'=>env('DB_HOST','127.0.0.1'),'port'=>env('DB_PORT','3306'),'database'=>env('DB_DATABASE','ukpr'),'username'=>env('DB_USERNAME'),'password'=>env('DB_PASSWORD'),'unix_socket'=>env('DB_SOCKET',''),'charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'','prefix_indexes'=>true,'strict'=>true,'engine'=>null],
 ],
 'migrations'=>['table'=>'migrations','update_date_on_publish'=>true],
];
`;
write('config/database.php',db);
write('config/filesystems.php',`<?php
return ['default'=>'local','disks'=>['local'=>['driver'=>'local','root'=>storage_path('app/private'),'throw'=>true,'serve'=>false]],'links'=>[]];
`);
let app=fs.readFileSync('config/app.php','utf8').replace("'timezone' => 'UTC'","'timezone' => 'Asia/Jakarta'");write('config/app.php',app);
write('.env.example',`APP_NAME="Universitas Kristen Palangka Raya"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://kampus.example.com
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
LOG_CHANNEL=single
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
MAIL_MAILER=log
SETUP_TOKEN=
`);
write('.env',fs.readFileSync('.env.example','utf8').replace('APP_ENV=production','APP_ENV=local').replace('APP_KEY=','APP_KEY=base64:'+crypto.randomBytes(32).toString('base64')).replace('https://kampus.example.com','http://127.0.0.1:8019').replace('DB_HOST=localhost','DB_HOST=127.0.0.1').replace('DB_PORT=3306','DB_PORT=3319').replace('DB_DATABASE=','DB_DATABASE=ukpr_local').replace('DB_USERNAME=','DB_USERNAME=root').replace('SESSION_SECURE_COOKIE=true','SESSION_SECURE_COOKIE=false'));
write('phpunit.xml',fs.readFileSync('phpunit.xml','utf8').replace('name="DB_CONNECTION" value="sqlite"','name="DB_CONNECTION" value="mysql"').replace('name="DB_DATABASE" value=":memory:"','name="DB_DATABASE" value="ukpr_test"').replace('<env name="DB_URL" value=""/>','<env name="DB_HOST" value="127.0.0.1"/><env name="DB_PORT" value="3319"/><env name="DB_USERNAME" value="root"/><env name="DB_PASSWORD" value=""/>'));
fs.appendFileSync('.gitignore','\n/.runtime/\n/dist/\n/.env.testing\n');
console.log('Konfigurasi MySQL dan aset siap.');
