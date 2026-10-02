<?php
namespace App\Http\Controllers;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class PublicController extends Controller {
    private function items(string $kind,int $limit=100) { return Content::where('kind',$kind)->published()->orderBy('position')->latest('published_at')->limit($limit)->get()->filter(fn($item)=>$item->visible()); }
    private function shared(): array {
        return ['settings'=>$this->items('settings')->pluck('body','slug'), 'menus'=>$this->items('menus')->whereNull('parent_id')];
    }
    public function home() {
        return view('public.home',array_merge($this->shared(),['hero'=>$this->items('banners')->first(),'banners'=>$this->items('banners'),'faculties'=>$this->items('faculties'),'news'=>$this->items('news',3),'events'=>$this->items('events',3),'announcements'=>Content::where('kind','announcements')->whereNull('unit_id')->published()->latest('published_at')->limit(3)->get(),'facilities'=>$this->items('facilities',3),'quicklinks'=>$this->items('quicklinks',12),'intro'=>$this->items('pages')->firstWhere('slug','beranda'),'stats'=>collect(['faculties','programs','facilities','events'])->mapWithKeys(fn($k)=>[$k=>Content::where('kind',$k)->published()->count()])]));
    }
    public function section(Request $request,string $section) {
        if (Content::where('kind','pages')->where('slug',$section)->published()->exists()) return $this->page($section);
        $kind=Content::SECTIONS[$section]??null; abort_unless($kind,404);
        $items=Content::where('kind',$kind)->published()->when($request->filled('q'),fn($q)=>$q->where('title','like','%'.mb_substr($request->string('q'),0,100).'%'))->where(function($q) { $q->whereNull('parent_id')->orWhereHas('parent',fn($p)=>$p->published()); })->orderBy('position')->latest('published_at')->paginate(12)->withQueryString();
        return view('public.list',array_merge($this->shared(),compact('kind','items','section'),['title'=>Content::KINDS[$kind]]));
    }
    private function page(string $slug) {
        $item=Content::where('kind','pages')->where('slug',$slug)->published()->firstOrFail();
        return $this->renderDetail($item);
    }
    public function detail(string $section,string $slug) {
        $kind=Content::SECTIONS[$section]??null; abort_unless($kind,404);
        $item=Content::where('kind',$kind)->where('slug',$slug)->published()->firstOrFail(); abort_unless($item->visible(),404);
        return $this->renderDetail($item);
    }
    private function renderDetail(Content $item) {
        $children=$item->children()->published()->get()->filter(fn($c)=>$c->visible());
        $related=in_array($item->slug,['profil','sejarah','visi-misi'],true)?$this->items('leaders'):collect();
        $notices=collect();
        if (in_array($item->kind,['faculties','programs'],true)) {
            $unitIds=$item->kind==='faculties' ? $children->pluck('id')->push($item->id)->all() : [$item->id];
            $notices=Content::where('kind','announcements')->whereIn('unit_id',$unitIds)->published()->with('unit')->latest('published_at')->limit(6)->get();
        }
        return view('public.detail',array_merge($this->shared(),compact('item','children','related','notices'),['title'=>$item->title]));
    }
    public function media(Request $request,Content $content) {
        abort_unless($content->visible() || ($request->user()?->isStaff() && $request->user()->canManage($content)),404);
        abort_unless($content->image_path && preg_match('~^media/[a-zA-Z0-9_-]+\.(webp|jpg|jpeg|png)$~D',$content->image_path) && Storage::disk('local')->exists($content->image_path),404);
        return response()->file(Storage::disk('local')->path($content->image_path),['Cache-Control'=>$content->visible()?'public, max-age=3600':'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
    public function sitemap() {
        $items=Content::whereIn('kind',array_unique(array_values(Content::SECTIONS)))->published()->get()->filter(fn($i)=>$i->visible());
        return response()->view('public.sitemap',compact('items'))->header('Content-Type','application/xml');
    }
    public function robots() { return response("User-agent: *\nDisallow: /admin\nDisallow: /login\nDisallow: /setup\nSitemap: ".url('/sitemap.xml')."\n")->header('Content-Type','text/plain'); }
}
