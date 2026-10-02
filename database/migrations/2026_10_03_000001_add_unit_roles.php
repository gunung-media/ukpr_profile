<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users',function(Blueprint $t) { $t->string('role',16)->nullable()->after('is_admin'); $t->foreignId('unit_id')->nullable()->after('role')->constrained('contents')->nullOnDelete(); });
        Schema::table('contents',fn(Blueprint $t)=>$t->foreignId('unit_id')->nullable()->after('parent_id')->constrained('contents')->restrictOnDelete());
    }
    public function down(): void {
        Schema::table('contents',fn(Blueprint $t)=>$t->dropConstrainedForeignId('unit_id'));
        Schema::table('users',function(Blueprint $t) { $t->dropConstrainedForeignId('unit_id'); $t->dropColumn('role'); });
    }
};
