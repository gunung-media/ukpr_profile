<?php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
Artisan::command('ukpr:admin {email} {--name=Admin UKPR}',function() {
    $password=$this->secret('Password (minimal 12 karakter, besar/kecil, angka, simbol)');
    $data=['name'=>$this->option('name'),'email'=>$this->argument('email'),'password'=>$password];
    $validation=Validator::make($data,['name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users','password'=>['required',Password::min(12)->mixedCase()->numbers()->symbols()]]);
    if ($validation->fails()) { foreach($validation->errors()->all() as $error) $this->error($error); return 1; }
    $user=new User($data); $user->is_admin=true; $user->save(); $this->info('Admin dibuat tanpa password default.');
})->purpose('Membuat akun admin dengan password interaktif');
