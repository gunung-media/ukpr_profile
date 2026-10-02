<?php
namespace App\Http\Controllers;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class AdminController extends Controller {
    private function kind(string $kind): void { abort_unless(isset(Content::KINDS[$kind]),404); abort_unless(in_array($kind,auth()->user()->allowedKinds(),true),403); }
    private function record(string $kind,Content $content): void { $this->kind($kind); abort_unless($content->kind===$kind,404); abort_unless(auth()->user()->canManage($content),403); }
    /** Unit admins may only create/delete announcements; faculty and program pages stay owned by the super admin. */
    private function canCreate(string $kind): bool { return auth()->user()->is_admin || $kind==='announcements'; }
    private function scoped(string $kind) {
        $user=auth()->user(); $query=Content::where('kind',$kind);
        if (!$user->is_admin) $query->whereIn($kind==='announcements' ? 'unit_id' : 'id',$user->unitIds());
        return $query;
    }
    public function dashboard() {
        $user=auth()->user();
        $counts=collect($user->allowedKinds())->mapWithKeys(fn($kind)=>[$kind=>$this->scoped($kind)->count()]);
        $recent=collect($user->allowedKinds())->flatMap(fn($kind)=>$this->scoped($kind)->latest('updated_at')->limit(6)->get())->sortByDesc('updated_at')->take(6)->values();
        $drafts=collect($user->allowedKinds())->sum(fn($kind)=>$this->scoped($kind)->where('is_published',false)->count());
        return view('admin.dashboard',compact('counts','user','recent','drafts'));
    }
    public function index(Request $request,string $kind) {
        $this->kind($kind);
        $items=$this->scoped($kind)->with('unit')->when($request->filled('q'),fn($q)=>$q->where('title','like','%'.mb_substr($request->string('q'),0,100).'%'))->orderBy('position')->latest('id')->paginate(20)->withQueryString();
        $canCreate=$this->canCreate($kind);
        return view('admin.index',compact('kind','items','canCreate'));
    }
    private function form(string $kind,Content $content) {
        $parentKind=['programs'=>'faculties','photos'=>'albums','menus'=>'menus'][$kind]??null;
        $parents=$parentKind ? Content::where('kind',$parentKind)->where('id','!=',$content->id??0)->orderBy('title')->get() : collect();
        $units=$kind==='announcements' ? $this->units() : collect();
        $locked=!auth()->user()->is_admin && in_array($kind,['faculties','programs'],true);
        return view('admin.form',compact('kind','content','parents','units','locked'));
    }
    private function units() {
        $user=auth()->user();
        return Content::whereIn('kind',['faculties','programs'])->when(!$user->is_admin,fn($q)=>$q->whereIn('id',$user->unitIds()))->orderBy('kind')->orderBy('title')->get();
    }
    public function create(string $kind) { $this->kind($kind); abort_unless($this->canCreate($kind),403); return $this->form($kind,new Content(['kind'=>$kind,'is_published'=>true])); }
    public function edit(string $kind,Content $content) { $this->record($kind,$content); return $this->form($kind,$content); }
    private function data(Request $request,string $kind,?Content $content=null): array {
        $parentKind=['programs'=>'faculties','photos'=>'albums','menus'=>'menus'][$kind]??null;
        $data=$request->validate([
            'title'=>'required|string|max:255', 'slug'=>['required','string','max:160','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',Rule::unique('contents')->where('kind',$kind)->ignore($content?->id)],
            'subtitle'=>$kind==='quicklinks' ? ['required',Rule::in(array_keys(Content::ICONS))] : 'nullable|string|max:255','summary'=>'nullable|string|max:2000','body'=>'nullable|string|max:100000',
            'seo_description'=>'nullable|string|max:300','position'=>'required|integer|min:0|max:100000', 'is_published'=>'required|boolean',
            'published_at'=>'nullable|date', 'starts_at'=>'nullable|date', 'ends_at'=>'nullable|date|after_or_equal:starts_at',
            'parent_id'=>array_filter([in_array($kind,['programs','photos'],true)?'required':'nullable','integer',$parentKind?Rule::exists('contents','id')->where('kind',$parentKind):'prohibited']),
            'link'=>[$kind==='quicklinks' ? 'required' : 'nullable','string','max:1000',function($attribute,$value,$fail) {
                if (!preg_match('~^(https?://[^\s]+|/(?!/)[a-zA-Z0-9/?=&_\-#.%]*)$~D',$value)) $fail('Gunakan URL https/http atau path internal seperti /pmb.');
            }],
            'unit_id'=>$kind==='announcements' ? [auth()->user()->is_admin ? 'nullable' : 'required','integer',Rule::in($this->units()->pluck('id')->all())] : ['exclude'],
            'image'=>['nullable','file','image','mimes:jpg,jpeg,png,webp','max:5120','dimensions:max_width=6000,max_height=6000',function($attribute,$file,$fail) { $dimensions=@getimagesize($file->getRealPath()); if(!$dimensions || $dimensions[0]*$dimensions[1]>8000000) $fail('Gambar maksimal 8 juta piksel.'); }],'remove_image'=>'nullable|boolean',
        ]);
        if ($kind==='pages' && in_array($data['slug'],['admin','login','logout','setup','media','up','berita','agenda','pengumuman','fakultas','prodi','pimpinan','galeri','fasilitas'],true)) throw ValidationException::withMessages(['slug'=>'Slug ini digunakan sistem.']);
        if ($kind==='menus' && !empty($data['parent_id'])) {
            $parent=Content::findOrFail($data['parent_id']);
            if ($parent->parent_id || $parent->id===$content?->id || ($content && $content->children()->exists())) throw ValidationException::withMessages(['parent_id'=>'Menu maksimal dua tingkat dan tidak boleh berputar.']);
        }
        unset($data['image'],$data['remove_image']);
        // Unit admins edit the content of their page, but structure (slug, faculty, order, status) stays with the super admin.
        if (!auth()->user()->is_admin && $content && in_array($kind,['faculties','programs'],true)) foreach(['slug','parent_id','position','is_published','published_at'] as $field) $data[$field]=$content->getRawOriginal($field);
        $data['kind']=$kind;
        if ($request->hasFile('image')) {
            // Re-encode pixels: remove metadata and any appended executable payload.
            $source=imagecreatefromstring(file_get_contents($request->file('image')->getRealPath()));
            if (!$source) throw ValidationException::withMessages(['image'=>'Gambar tidak dapat dibaca.']);
            $width=imagesx($source); $height=imagesy($source); $scale=min(1,1920/$width,1920/$height);
            $target=imagecreatetruecolor(max(1,(int)($width*$scale)),max(1,(int)($height*$scale)));
            imagealphablending($target,false); imagesavealpha($target,true);
            imagecopyresampled($target,$source,0,0,0,0,imagesx($target),imagesy($target),$width,$height);
            ob_start(); imagewebp($target,null,85); $bytes=ob_get_clean(); imagedestroy($source); imagedestroy($target);
            $path='media/'.bin2hex(random_bytes(16)).'.webp'; Storage::disk('local')->put($path,$bytes); $data['image_path']=$path;
        } elseif ($request->boolean('remove_image')) $data['image_path']=null;
        return $data;
    }
    public function store(Request $request,string $kind) {
        $this->kind($kind); abort_unless($this->canCreate($kind),403); $data=$this->data($request,$kind);
        try { Content::create($data); } catch (\Throwable $e) { if (!empty($data['image_path'])) Storage::disk('local')->delete($data['image_path']); throw $e; }
        return redirect()->route('admin.index',$kind)->with('status','Konten berhasil ditambahkan.');
    }
    public function update(Request $request,string $kind,Content $content) {
        $this->record($kind,$content); $data=$this->data($request,$kind,$content); $old=$content->image_path;
        try { $content->update($data); } catch (\Throwable $e) { if (!empty($data['image_path']) && $data['image_path']!==$old) Storage::disk('local')->delete($data['image_path']); throw $e; }
        if (array_key_exists('image_path',$data) && $old && $old!==$data['image_path'] && !Content::where('image_path',$old)->exists()) Storage::disk('local')->delete($old);
        return redirect()->route('admin.index',$kind)->with('status','Perubahan disimpan.');
    }
    public function destroy(string $kind,Content $content) {
        $this->record($kind,$content); abort_unless($this->canCreate($kind),403);
        if ($content->children()->exists() || Content::where('unit_id',$content->id)->exists() || \App\Models\User::where('unit_id',$content->id)->exists()) return back()->withErrors(['content'=>'Hapus atau pindahkan konten anak, pengumuman unit, dan akun pengelola yang terkait terlebih dahulu.']);
        $path=$content->image_path; $content->delete(); if ($path && !Content::where('image_path',$path)->exists()) Storage::disk('local')->delete($path);
        return redirect()->route('admin.index',$kind)->with('status','Konten dihapus.');
    }
}
