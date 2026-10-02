<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public const ROLES = ['super'=>'Super admin','faculty'=>'Admin fakultas','program'=>'Admin program studi'];

    public function unit() { return $this->belongsTo(Content::class, 'unit_id'); }

    public function roleKey(): ?string { return $this->is_admin ? 'super' : (in_array($this->role,['faculty','program'],true) && $this->unit_id ? $this->role : null); }

    public function isStaff(): bool { return $this->roleKey()!==null; }

    /** Faculty/program IDs this account manages; a faculty admin also manages its programs. */
    public function unitIds(): array {
        return once(function() {
            if ($this->roleKey()==='faculty') return array_merge([$this->unit_id],Content::where('kind','programs')->where('parent_id',$this->unit_id)->pluck('id')->all());
            return $this->roleKey()==='program' ? [$this->unit_id] : [];
        });
    }

    public function allowedKinds(): array {
        return match($this->roleKey()) { 'super'=>array_keys(Content::KINDS), 'faculty'=>['faculties','programs','announcements'], 'program'=>['programs','announcements'], default=>[] };
    }

    public function canManage(Content $content): bool {
        if ($this->is_admin) return true;
        if (!in_array($content->kind,$this->allowedKinds(),true)) return false;
        return in_array($content->kind==='announcements' ? $content->unit_id : $content->id,$this->unitIds(),true);
    }
}
