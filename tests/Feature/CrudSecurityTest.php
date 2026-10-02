<?php
namespace Tests\Feature;
use App\Models\{Content,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash,Storage};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
class CrudSecurityTest extends TestCase {
    use RefreshDatabase;
    private function admin(): User { $user=User::factory()->create(); $user->is_admin=true; $user->save(); return $user; }
    public static function kinds(): array { return array_map(fn($kind)=>[$kind],array_keys(Content::KINDS)); }
    private function data(string $kind): array {
        $data=['title'=>'Konten pengujian','slug'=>'konten-uji','body'=>'## Isi aman','position'=>0,'is_published'=>1];
        if(in_array($kind,['programs','photos'])) $data['parent_id']=Content::create(['kind'=>$kind==='programs'?'faculties':'albums','slug'=>'induk','title'=>'Induk','is_published'=>true])->id;
        if($kind==='quicklinks') $data+=['subtitle'=>'laptop','link'=>'https://siakad.example.test'];
        return $data;
    }
    #[DataProvider('kinds')]
    public function test_all_content_crud_and_guest_denial(string $kind): void {
        $data=$this->data($kind);
        $this->get('/admin/'.$kind)->assertRedirect('/login');
        $this->post('/admin/'.$kind,$data)->assertRedirect('/login');
        $this->actingAs($this->admin());
        $this->get('/admin/'.$kind)->assertOk(); $this->get('/admin/'.$kind.'/create')->assertOk();
        $this->post('/admin/'.$kind,$data)->assertRedirect('/admin/'.$kind);
        $item=Content::where('kind',$kind)->where('slug','konten-uji')->firstOrFail();
        $this->get('/admin/'.$kind.'/'.$item->id.'/edit')->assertOk();
        $this->put('/admin/'.$kind.'/'.$item->id,array_merge($data,['title'=>'Diubah']))->assertRedirect('/admin/'.$kind);
        $this->assertDatabaseHas('contents',['id'=>$item->id,'title'=>'Diubah']);
        $this->delete('/admin/'.$kind.'/'.$item->id)->assertRedirect('/admin/'.$kind);
        $this->assertDatabaseMissing('contents',['id'=>$item->id]);
    }
    public function test_login_logout_hash_and_regular_user_authorization(): void {
        $user=$this->admin(); $user->password='Ukpr-test!2026Secure'; $user->save();
        $this->assertTrue(Hash::check('Ukpr-test!2026Secure',$user->fresh()->password));
        $this->post('/login',['email'=>$user->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->post('/login',['email'=>$user->email,'password'=>'Ukpr-test!2026Secure'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user); $this->post('/logout')->assertRedirect('/login'); $this->assertGuest();
        $regular=User::factory()->create(); $this->actingAs($regular)->get('/admin')->assertForbidden();
        foreach(array_keys(Content::KINDS) as $kind) $this->actingAs($regular)->post('/admin/'.$kind,['title'=>'X'])->assertForbidden();
    }
    public function test_kind_scoping_parent_validation_and_delete_protection(): void {
        $this->actingAs($this->admin());
        $faculty=Content::create(['kind'=>'faculties','slug'=>'f','title'=>'F','is_published'=>true]);
        $album=Content::create(['kind'=>'albums','slug'=>'a','title'=>'A','is_published'=>true]);
        $program=Content::create(['kind'=>'programs','slug'=>'p','title'=>'P','parent_id'=>$faculty->id,'is_published'=>true]);
        $this->get('/admin/news/'.$program->id.'/edit')->assertNotFound();
        $this->put('/admin/news/'.$program->id,['title'=>'Injected'])->assertNotFound();
        $this->delete('/admin/news/'.$program->id)->assertNotFound();
        $data=['slug'=>'p','title'=>'P','position'=>0,'is_published'=>1,'parent_id'=>$album->id];
        $this->put('/admin/programs/'.$program->id,$data)->assertSessionHasErrors('parent_id');
        $this->delete('/admin/faculties/'.$faculty->id)->assertSessionHasErrors('content');
        $this->assertDatabaseHas('contents',['id'=>$faculty->id]);
        $this->post('/admin/menus',['slug'=>'unsafe','title'=>'unsafe','position'=>0,'is_published'=>1,'link'=>'javascript:alert(1)'])->assertSessionHasErrors('link');
    }
    public function test_upload_reencoded_private_drafts_and_replacement_cleanup(): void {
        Storage::fake('local'); $this->actingAs($this->admin());
        $data=$this->data('news'); $data['image']=UploadedFile::fake()->image('image.jpg',400,300);
        $this->post('/admin/news',$data)->assertRedirect('/admin/news');
        $item=Content::where('kind','news')->firstOrFail(); $path=$item->image_path;
        $this->assertStringEndsWith('.webp',$path); Storage::disk('local')->assertExists($path);
        $this->get('/media/'.$item->id)->assertOk();
        $item->update(['is_published'=>false]); $this->get('/media/'.$item->id)->assertOk();
        $this->post('/logout'); $this->get('/media/'.$item->id)->assertNotFound(); $this->get('/berita/'.$item->slug)->assertNotFound();
        $this->actingAs($this->admin());unset($data['image']);$data['remove_image']=1;
        $this->put('/admin/news/'.$item->id,$data)->assertRedirect('/admin/news');Storage::disk('local')->assertMissing($path);
        $data['image']=UploadedFile::fake()->create('shell.php',1,'application/x-httpd-php');
        $this->post('/admin/news',array_merge($data,['slug'=>'evil']))->assertSessionHasErrors('image');
    }
    public function test_public_pages_safe_rendering_scheduling_and_parent_visibility(): void {
        $this->seed();
        foreach(['/','/profil','/sejarah','/visi-misi','/pmb','/kontak','/pimpinan','/fakultas','/prodi','/berita','/agenda','/pengumuman','/fasilitas','/galeri','/sitemap.xml','/robots.txt','/login'] as $path) $this->get($path)->assertOk();
        foreach(Content::whereIn('kind',array_values(Content::SECTIONS))->published()->get() as $item) $this->get($item->publicUrl())->assertOk();
        $article=Content::create(['kind'=>'news','title'=>'Safe','slug'=>'safe','body'=>'<script>alert(1)</script> [unsafe](javascript:alert(1))','is_published'=>true]);
        $this->get('/berita/safe')->assertOk()->assertDontSee('<script>alert(1)</script>',false)->assertDontSee('href="javascript:',false);
        $article->update(['published_at'=>now()->addDay()]);$this->get('/berita/safe')->assertNotFound();
        $parent=Content::create(['kind'=>'faculties','title'=>'Draft','slug'=>'draft','is_published'=>false]);
        $child=Content::create(['kind'=>'programs','title'=>'Hidden','slug'=>'hidden','is_published'=>true,'parent_id'=>$parent->id]);
        $this->get('/prodi/hidden')->assertNotFound(); $this->get('/prodi')->assertDontSee('Hidden');
        $this->get('/admin/not-a-kind')->assertRedirect('/login'); $this->get('/no-such-page')->assertNotFound();
    }
    public function test_setup_disabled_unless_configured_and_locked_after_creation(): void {
        $this->get('/setup')->assertNotFound(); config(['ukpr.setup_token'=>'random-private-install-token']);
        $this->get('/setup')->assertOk();
        $data=['name'=>'Owner','email'=>'owner@example.test','password'=>'OwnerPassword!2026','password_confirmation'=>'OwnerPassword!2026','token'=>'wrong'];
        $this->post('/setup',$data)->assertForbidden();$data['token']='random-private-install-token';
        $this->post('/setup',$data)->assertRedirect('/login');$this->assertTrue(User::first()->is_admin);
        $this->get('/setup')->assertNotFound(); $this->post('/setup',$data)->assertNotFound();
    }
}
