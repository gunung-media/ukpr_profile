<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    // Default homepage quick-access cards; editable afterwards from Admin → Akses cepat.
    public const DEFAULTS = [['pmb','PMB','Penerimaan Mahasiswa Baru','/pmb','graduation-cap'],['fakultas','Fakultas','Pilihan fakultas','/fakultas','building'],['program-studi','Program Studi','Temukan bidangmu','/prodi','book-open'],['berita','Berita','Kabar terbaru kampus','/berita','newspaper'],['agenda','Agenda','Kegiatan mendatang','/agenda','calendar'],['galeri','Galeri','Potret kehidupan kampus','/galeri','image']];
    public function up(): void {
        if (DB::table('contents')->where('kind','quicklinks')->exists()) return;
        foreach (self::DEFAULTS as $i=>[$slug,$title,$summary,$link,$icon]) DB::table('contents')->insert(['kind'=>'quicklinks','slug'=>$slug,'title'=>$title,'summary'=>$summary,'link'=>$link,'subtitle'=>$icon,'position'=>$i,'is_published'=>true,'created_at'=>now(),'updated_at'=>now()]);
    }
    public function down(): void { DB::table('contents')->where('kind','quicklinks')->delete(); }
};
