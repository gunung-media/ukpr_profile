<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class Content extends Model {
    public const KINDS = ['pages'=>'Halaman','faculties'=>'Fakultas','programs'=>'Program studi','leaders'=>'Pimpinan','news'=>'Berita','events'=>'Agenda','announcements'=>'Pengumuman','facilities'=>'Fasilitas','albums'=>'Album galeri','photos'=>'Foto galeri','banners'=>'Banner','menus'=>'Menu','quicklinks'=>'Akses cepat','settings'=>'Pengaturan'];
    public const KIND_ICONS = ['pages'=>'file-text','faculties'=>'building','programs'=>'book-open','leaders'=>'user','news'=>'newspaper','events'=>'calendar','announcements'=>'megaphone','facilities'=>'home','albums'=>'image','photos'=>'image','banners'=>'layers','menus'=>'list','quicklinks'=>'link','settings'=>'settings'];
    /** Icon names available in resources/views/components/icon.blade.php, selectable for quick links. */
    public const ICONS = ['graduation-cap'=>'Toga','building'=>'Gedung','book-open'=>'Buku','newspaper'=>'Koran','calendar'=>'Kalender','image'=>'Gambar','megaphone'=>'Pengumuman','laptop'=>'Laptop','mail'=>'Email','users'=>'Pengguna','user'=>'Profil','globe'=>'Globe','award'=>'Penghargaan','shield-check'=>'Perisai','heart'=>'Hati','cross'=>'Salib','home'=>'Rumah','phone'=>'Telepon','map-pin'=>'Lokasi','search'=>'Cari','lightbulb'=>'Ide','pencil'=>'Formulir','play-circle'=>'Video','sprout'=>'Tunas','clock'=>'Jam','arrow-up-right'=>'Tautan'];
    public const SECTIONS = ['profil'=>'pages','fakultas'=>'faculties','prodi'=>'programs','pimpinan'=>'leaders','berita'=>'news','agenda'=>'events','pengumuman'=>'announcements','fasilitas'=>'facilities','galeri'=>'albums'];
    protected $fillable = ['kind','slug','title','subtitle','summary','body','image_path','parent_id','unit_id','position','is_published','published_at','starts_at','ends_at','link','seo_description'];
    protected function casts(): array { return ['is_published'=>'boolean','published_at'=>'datetime','starts_at'=>'datetime','ends_at'=>'datetime']; }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function unit() { return $this->belongsTo(self::class, 'unit_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('position'); }
    public function scopePublished(Builder $query): Builder { return $query->where('is_published',true)->where(fn($q)=>$q->whereNull('published_at')->orWhere('published_at','<=',now())); }
    public function visible(): bool {
        if (!$this->is_published || ($this->published_at && $this->published_at->isFuture())) return false;
        return !$this->parent_id || ($this->parent && $this->parent->visible());
    }
    public function publicUrl(): string {
        if ($this->kind==='pages') return url('/'.$this->slug);
        $section=array_search($this->kind,self::SECTIONS,true);
        return $section ? url('/'.$section.'/'.$this->slug) : url('/');
    }
}
