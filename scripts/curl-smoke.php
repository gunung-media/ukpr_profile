<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Content;
if(!app()->environment('local') || config('database.connections.mysql.database')!=='ukpr_local')throw new RuntimeException('Smoke test hanya untuk ukpr_local.');
$base=$argv[1]??'http://127.0.0.1:8019';
$credentials=json_decode(file_get_contents(__DIR__.'/../.runtime/local-account.json'),true);
$directory=__DIR__.'/../.runtime';$jar=$directory.'/curl-cookies.txt';@unlink($jar);$assertions=0;$results=[];
function request(string $method,string $path,array $data=[],bool $cookies=true): array {
    global $base,$jar;
    $handle=curl_init($base.$path);
    curl_setopt_array($handle,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_TIMEOUT=>30,CURLOPT_PROXY=>'',CURLOPT_FOLLOWLOCATION=>false]);
    if($cookies){curl_setopt($handle,CURLOPT_COOKIEJAR,$jar);curl_setopt($handle,CURLOPT_COOKIEFILE,$jar);}
    if($data)curl_setopt($handle,CURLOPT_POSTFIELDS,isset($data['image'])?$data:http_build_query($data));
    $raw=curl_exec($handle);if($raw===false)throw new RuntimeException(curl_error($handle));$code=curl_getinfo($handle,CURLINFO_HTTP_CODE);$size=curl_getinfo($handle,CURLINFO_HEADER_SIZE);curl_close($handle);
    return ['code'=>$code,'headers'=>substr($raw,0,$size),'body'=>substr($raw,$size)];
}
function expect(bool $condition,string $message): void { global $assertions; $assertions++;if(!$condition)throw new RuntimeException($message); }
function token(string $path): string { $response=request('GET',$path);expect($response['code']===200,'Form '.$path);preg_match('/name="_token" value="([^"]+)"/',$response['body'],$match);expect(!empty($match[1]),'CSRF token '.$path);return html_entity_decode($match[1]); }
function login(array $credentials): void { $csrf=token('/login');$r=request('POST','/login',array_merge($credentials,['_token'=>$csrf]));expect($r['code']===302 && str_contains($r['headers'],'/admin'),'Login admin'); }
try {
    $pages=['/','/profil','/sejarah','/visi-misi','/pmb','/kontak','/fakultas','/prodi','/pimpinan','/berita','/agenda','/pengumuman','/fasilitas','/galeri','/login','/sitemap.xml','/robots.txt'];
    foreach(Content::whereIn('kind',array_values(Content::SECTIONS))->published()->get() as $item)$pages[]=parse_url($item->publicUrl(),PHP_URL_PATH);
    foreach(array_unique($pages) as $path){$r=request('GET',$path,[],false);expect($r['code']===200,'Halaman publik '.$path);expect(!str_contains($r['body'],'Exception'),'Tidak ada exception '.$path);}
    expect(request('POST','/login',$credentials,false)['code']===419,'CSRF tanpa token ditolak');
    $csrf=token('/login');$r=request('POST','/login',['_token'=>$csrf,'email'=>$credentials['email'],'password'=>'wrong']);expect($r['code']===302,'Login salah ditolak');
    login($credentials);
    foreach(array_keys(Content::KINDS) as $kind){
        expect(request('GET','/admin/'.$kind)['code']===200,'List '.$kind);$csrf=token('/admin/'.$kind.'/create');
        $data=['_token'=>$csrf,'title'=>'Curl test '.$kind,'slug'=>'curl-test-'.$kind,'summary'=>'Pengujian otomatis','body'=>'Konten uji','position'=>99,'is_published'=>1];
        if($kind==='programs')$data['parent_id']=Content::where('kind','faculties')->firstOrFail()->id;
        if($kind==='photos')$data['parent_id']=Content::where('kind','albums')->firstOrFail()->id;
        expect(request('POST','/admin/'.$kind,$data,false)['code']===419,'CSRF create '.$kind);
        expect(request('POST','/admin/'.$kind,$data)['code']===302,'Create '.$kind);
        $item=Content::where('kind',$kind)->where('slug',$data['slug'])->firstOrFail();$path='/admin/'.$kind.'/'.$item->id;
        expect(request('GET',$path.'/edit')['code']===200,'Read '.$kind);
        foreach(['GET','PUT','DELETE'] as $method){$r=request($method,$method==='GET'?$path.'/edit':$path,$method==='GET'?[]:$data,false);expect(in_array($r['code'],[302,419]),'Guest '.$method.' '.$kind);}
        expect(request('POST',$path,array_merge($data,['_method'=>'PUT','title'=>'Updated '.$kind]))['code']===302,'Update '.$kind);
        expect($item->fresh()->title==='Updated '.$kind,'DB update '.$kind);
        expect(request('POST','/admin/news/'.$item->id,array_merge($data,['_method'=>'PUT']))['code']===($kind==='news'?302:404),'Scope jenis '.$kind);
        expect(request('POST',$path,['_token'=>$csrf,'_method'=>'DELETE'])['code']===302,'Delete '.$kind);expect(!Content::find($item->id),'DB delete '.$kind);
        $results[$kind]='CRUD + guest + CSRF + scope OK';
    }
    $csrf=token('/admin/news/create');$data=['_token'=>$csrf,'title'=>'Upload curl','slug'=>'curl-upload','position'=>0,'is_published'=>0,'image'=>new CURLFile(storage_path('app/private/media/campus.webp'),'image/webp','campus.webp')];
    expect(request('POST','/admin/news',$data)['code']===302,'Upload cURL multipart');$image=Content::where('slug','curl-upload')->firstOrFail();
    expect(request('GET','/media/'.$image->id)['code']===200,'Admin melihat media draft');expect(request('GET','/media/'.$image->id,[],false)['code']===404,'Guest tidak melihat media draft');
    expect(request('POST','/admin/news/'.$image->id,['_token'=>$csrf,'_method'=>'DELETE'])['code']===302,'Hapus media');expect(!is_file(storage_path('app/private/'.$image->image_path)),'File upload terhapus');
    $csrf=token('/admin');request('POST','/logout',['_token'=>$csrf]);expect(request('GET','/admin')['code']===302,'Logout dan akses kembali');
    $csrf=token('/login');$r=request('POST','/login',['_token'=>$csrf,'email'=>'member@ukpr.test','password'=>$credentials['password']]);expect($r['code']===403,'Login non-admin ditolak');
    login($credentials);request('POST','/logout',['_token'=>token('/admin')]);
    $report=['status'=>'PASS','assertions'=>$assertions,'public_pages'=>count(array_unique($pages)),'crud'=>$results,'upload'=>'PASS','authorization'=>'PASS','csrf'=>'PASS'];
    file_put_contents($directory.'/curl-result.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode($report,JSON_PRETTY_PRINT)."\n";
}catch(Throwable $error){file_put_contents($directory.'/curl-result.json',json_encode(['status'=>'FAIL','assertions'=>$assertions,'error'=>$error->getMessage()],JSON_PRETTY_PRINT));fwrite(STDERR,$error->getMessage()."\n");exit(1);}
