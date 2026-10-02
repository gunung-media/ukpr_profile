<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users',fn(Blueprint $t)=>$t->boolean('is_admin')->default(false));
        Schema::create('contents',function(Blueprint $t) {
            $t->id(); $t->string('kind',24); $t->string('slug',160); $t->string('title');
            $t->string('subtitle')->nullable(); $t->text('summary')->nullable(); $t->longText('body')->nullable();
            $t->string('image_path')->nullable(); $t->foreignId('parent_id')->nullable()->constrained('contents')->restrictOnDelete();
            $t->unsignedInteger('position')->default(0); $t->boolean('is_published')->default(false);
            $t->timestamp('published_at')->nullable(); $t->dateTime('starts_at')->nullable(); $t->dateTime('ends_at')->nullable();
            $t->string('link',1000)->nullable(); $t->string('seo_description',300)->nullable(); $t->timestamps();
            $t->unique(['kind','slug']); $t->index(['kind','is_published','position']);
        });
    }
    public function down(): void { Schema::dropIfExists('contents'); Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('is_admin')); }
};
