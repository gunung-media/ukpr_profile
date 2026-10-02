<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
class SetupController extends Controller {
    private function available(): void { abort_unless(config('ukpr.setup_token') && !User::where('is_admin',true)->exists(),404); }
    public function form() { $this->available(); return view('auth.setup'); }
    public function store(Request $request) {
        $this->available();
        $data=$request->validate(['token'=>'required|string','name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users','password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()->symbols()]]);
        abort_unless(hash_equals(config('ukpr.setup_token'),$data['token']),403);
        $lock='ukpr_admin_'.substr(hash('sha256',base_path()),0,16);
        $result=DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired',[$lock]);
        abort_unless((int)$result->acquired===1,503);
        try { abort_if(User::where('is_admin',true)->exists(),404); $user=new User(collect($data)->only('name','email','password')->all()); $user->is_admin=true; $user->save(); }
        finally { DB::select('SELECT RELEASE_LOCK(?)',[$lock]); }
        return redirect('/login')->with('status','Admin berhasil dibuat. Hapus SETUP_TOKEN dari .env.');
    }
}
