<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
    public function form() { return view('auth.login'); }
    public function login(Request $request) {
        $data=$request->validate(['email'=>'required|email|max:255','password'=>'required|string|max:255']);
        if (!Auth::attempt($data)) return back()->withErrors(['email'=>'Email atau password tidak sesuai.'])->onlyInput('email');
        $request->session()->regenerate();
        if (!$request->user()->isStaff()) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); abort(403); }
        return redirect()->intended(route('admin.dashboard'));
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/login'); }
}
