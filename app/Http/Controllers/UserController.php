<?php
namespace App\Http\Controllers;
use App\Models\{Content,User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
class UserController extends Controller {
    private function units() { return Content::whereIn('kind',['faculties','programs'])->with('parent')->orderBy('kind')->orderBy('title')->get(); }
    public function index() { return view('admin.users.index',['users'=>User::with('unit')->orderByDesc('is_admin')->orderBy('name')->paginate(30)]); }
    public function create() { return view('admin.users.form',['account'=>new User(),'units'=>$this->units()]); }
    public function edit(User $user) { return view('admin.users.form',['account'=>$user,'units'=>$this->units()]); }
    private function save(Request $request,User $user) {
        $data=$request->validate([
            'name'=>'required|string|max:100','email'=>['required','email','max:255',Rule::unique('users')->ignore($user->id)],
            'password'=>[$user->exists ? 'nullable' : 'required','confirmed',Password::min(12)->mixedCase()->numbers()->symbols()],
            'role'=>['required',Rule::in(array_keys(User::ROLES))],
            'unit_id'=>['exclude_if:role,super','required','integer',Rule::exists('contents','id')->where('kind',$request->input('role')==='program' ? 'programs' : 'faculties')],
        ]);
        // Prevent the signed-in super admin from locking themselves out.
        if ($user->is($request->user()) && $data['role']!=='super') throw ValidationException::withMessages(['role'=>'Anda tidak dapat menurunkan level akun sendiri.']);
        $user->fill(collect($data)->only('name','email')->all());
        if (!empty($data['password'])) $user->password=$data['password'];
        $user->is_admin=$data['role']==='super'; $user->role=$user->is_admin ? null : $data['role']; $user->unit_id=$user->is_admin ? null : $data['unit_id'];
        $user->save();
        return redirect()->route('admin.users.index')->with('status','Akun pengelola disimpan.');
    }
    public function store(Request $request) { return $this->save($request,new User()); }
    public function update(Request $request,User $user) { return $this->save($request,$user); }
    public function destroy(Request $request,User $user) {
        if ($user->is($request->user())) return back()->withErrors(['user'=>'Anda tidak dapat menghapus akun sendiri.']);
        $user->delete();
        return redirect()->route('admin.users.index')->with('status','Akun pengelola dihapus.');
    }
}
