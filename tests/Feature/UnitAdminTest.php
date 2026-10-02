<?php
namespace Tests\Feature;
use App\Models\{Content,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class UnitAdminTest extends TestCase {
    use RefreshDatabase;
    private Content $faculty; private Content $otherFaculty; private Content $program; private Content $otherProgram;
    protected function setUp(): void {
        parent::setUp();
        $make=fn($kind,$slug,$parent=null)=>Content::create(['kind'=>$kind,'slug'=>$slug,'title'=>ucfirst($slug),'is_published'=>true,'parent_id'=>$parent]);
        $this->faculty=$make('faculties','teknik'); $this->otherFaculty=$make('faculties','hukum');
        $this->program=$make('programs','sipil',$this->faculty->id); $this->otherProgram=$make('programs','ilmu-hukum',$this->otherFaculty->id);
    }
    private function account(string $role,Content $unit): User { $user=User::factory()->create(); $user->role=$role; $user->unit_id=$unit->id; $user->save(); return $user; }
    private function super(): User { $user=User::factory()->create(); $user->is_admin=true; $user->save(); return $user; }
    private function page(Content $c,array $extra=[]): array { return array_merge(['title'=>'Diubah unit','slug'=>'diganti','body'=>'Isi','position'=>99,'is_published'=>0,'parent_id'=>$this->otherFaculty->id],$extra); }

    public function test_faculty_admin_edits_own_faculty_and_programs_but_not_structure_or_others(): void {
        $admin=$this->account('faculty',$this->faculty); $this->actingAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Teknik');
        $this->get('/admin/faculties/'.$this->faculty->id.'/edit')->assertOk()->assertDontSee('name="slug" required',false);
        $this->get('/admin/programs/'.$this->program->id.'/edit')->assertOk();
        $this->get('/admin/announcements/create')->assertOk()->assertSee('Sipil');
        $this->get('/admin/faculties')->assertOk()->assertSee('Teknik')->assertDontSee('Hukum');
        $this->put('/admin/faculties/'.$this->faculty->id,$this->page($this->faculty,['parent_id'=>null]))->assertRedirect('/admin/faculties');
        $this->faculty->refresh(); $this->assertSame('Diubah unit',$this->faculty->title); $this->assertSame('teknik',$this->faculty->slug); $this->assertTrue($this->faculty->is_published); $this->assertSame(0,$this->faculty->position);
        $this->put('/admin/programs/'.$this->program->id,$this->page($this->program))->assertRedirect('/admin/programs');
        $this->assertSame($this->faculty->id,$this->program->fresh()->parent_id);
        $this->get('/admin/faculties/'.$this->otherFaculty->id.'/edit')->assertForbidden();
        $this->put('/admin/programs/'.$this->otherProgram->id,$this->page($this->otherProgram))->assertForbidden();
        $this->get('/admin/faculties/create')->assertForbidden();
        $this->post('/admin/programs',['title'=>'X','slug'=>'x','position'=>0,'is_published'=>1,'parent_id'=>$this->faculty->id])->assertForbidden();
        $this->delete('/admin/programs/'.$this->program->id)->assertForbidden();
        foreach(['news','pages','settings','menus','banners'] as $kind) $this->get('/admin/'.$kind)->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_unit_announcements_are_scoped_and_shown_on_unit_pages(): void {
        $admin=$this->account('program',$this->program); $this->actingAs($admin);
        $data=['title'=>'Jadwal sidang sipil','slug'=>'jadwal-sidang','body'=>'Isi','position'=>0,'is_published'=>1];
        $this->post('/admin/announcements',$data)->assertSessionHasErrors('unit_id');
        $this->post('/admin/announcements',$data+['unit_id'=>$this->otherProgram->id])->assertSessionHasErrors('unit_id');
        $this->post('/admin/announcements',$data+['unit_id'=>$this->faculty->id])->assertSessionHasErrors('unit_id');
        $this->post('/admin/announcements',$data+['unit_id'=>$this->program->id])->assertRedirect('/admin/announcements');
        $notice=Content::where('slug','jadwal-sidang')->firstOrFail(); $this->assertSame($this->program->id,$notice->unit_id);
        $foreign=Content::create(['kind'=>'announcements','slug'=>'hukum-info','title'=>'Info hukum','is_published'=>true,'unit_id'=>$this->otherProgram->id]);
        $this->get('/admin/announcements')->assertSee('Jadwal sidang sipil')->assertDontSee('Info hukum');
        $this->get('/admin/announcements/'.$foreign->id.'/edit')->assertForbidden();
        $this->delete('/admin/announcements/'.$foreign->id)->assertForbidden();
        $this->post('/logout');
        $this->get('/prodi/sipil')->assertOk()->assertSee('Jadwal sidang sipil')->assertDontSee('Info hukum');
        $this->get('/fakultas/teknik')->assertOk()->assertSee('Jadwal sidang sipil');
        $this->get('/')->assertOk()->assertDontSee('Jadwal sidang sipil');
        $this->get('/pengumuman/jadwal-sidang')->assertOk()->assertSee('Sipil');
        $this->delete('/admin/faculties/'.$this->faculty->id)->assertRedirect('/login');
    }

    public function test_super_admin_manages_accounts_and_unit_requirements(): void {
        $super=$this->super(); $this->actingAs($super);
        $this->get('/admin/users')->assertOk(); $this->get('/admin/users/create')->assertOk()->assertSee('Teknik'); $this->get('/admin/users/'.$super->id.'/edit')->assertOk(); $this->get('/admin/announcements/create')->assertOk()->assertSee('Seluruh kampus');
        $password='Unit-Admin!2026Pass';
        $this->post('/admin/users',['name'=>'Admin Teknik','email'=>'teknik@ukpr.test','password'=>$password,'password_confirmation'=>$password,'role'=>'faculty','unit_id'=>$this->program->id])->assertSessionHasErrors('unit_id');
        $this->post('/admin/users',['name'=>'Admin Teknik','email'=>'teknik@ukpr.test','password'=>$password,'password_confirmation'=>$password,'role'=>'faculty','unit_id'=>$this->faculty->id])->assertRedirect('/admin/users');
        $user=User::where('email','teknik@ukpr.test')->firstOrFail(); $this->assertFalse($user->is_admin); $this->assertSame('faculty',$user->roleKey());
        $this->put('/admin/users/'.$super->id,['name'=>'S','email'=>$super->email,'role'=>'program','unit_id'=>$this->program->id])->assertSessionHasErrors('role');
        $this->delete('/admin/users/'.$super->id)->assertSessionHasErrors('user');
        $this->delete('/admin/faculties/'.$this->faculty->id)->assertSessionHasErrors('content');
        $this->post('/logout');
        $this->post('/login',['email'=>'teknik@ukpr.test','password'=>$password])->assertRedirect('/admin');
        $plain=User::factory()->create(); $this->post('/logout'); $this->actingAs($plain)->get('/admin')->assertForbidden();
    }
    public function test_quick_links_are_managed_dynamically_from_admin(): void {
        $this->get("/")->assertOk()->assertSee("<h3>PMB</h3>",false);
        $this->actingAs($this->super());
        $this->post("/admin/quicklinks",["title"=>"Siakad","slug"=>"siakad","summary"=>"Sistem akademik","subtitle"=>"evil-icon","link"=>"https://siakad.ukpr.test","position"=>9,"is_published"=>1])->assertSessionHasErrors("subtitle");
        $this->post("/admin/quicklinks",["title"=>"Siakad","slug"=>"siakad","summary"=>"Sistem akademik","subtitle"=>"laptop","position"=>9,"is_published"=>1])->assertSessionHasErrors("link");
        $this->post("/admin/quicklinks",["title"=>"Siakad","slug"=>"siakad","summary"=>"Sistem akademik","subtitle"=>"laptop","link"=>"https://siakad.ukpr.test","position"=>9,"is_published"=>1])->assertRedirect("/admin/quicklinks");
        $pmb=Content::where("kind","quicklinks")->where("slug","pmb")->firstOrFail(); $pmb->delete();
        $this->get("/admin/quicklinks/create")->assertOk()->assertSee("Ikon");
        $this->get("/")->assertSee("SIAKAD")->assertSee("https://siakad.ukpr.test",false)->assertDontSee("<h3>PMB</h3>",false);
        $unit=$this->account("faculty",$this->faculty); $this->actingAs($unit)->get("/admin/quicklinks")->assertForbidden();
    }
}
