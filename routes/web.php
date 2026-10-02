<?php

use App\Http\Controllers\{AdminController, AuthController, PublicController, SetupController, UserController};
use Illuminate\Support\Facades\Route;
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/media/{content}', [PublicController::class, 'media'])->name('media');
Route::get('/sitemap.xml', [PublicController::class, 'sitemap']);
Route::get('/robots.txt', [PublicController::class, 'robots']);
Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/setup', [SetupController::class, 'form']);
Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:5,1');
Route::middleware(['auth', 'admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});
Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/{kind}', [AdminController::class, 'index'])->name('index');
    Route::get('/{kind}/create', [AdminController::class, 'create'])->name('create');
    Route::post('/{kind}', [AdminController::class, 'store'])->name('store');
    Route::get('/{kind}/{content}/edit', [AdminController::class, 'edit'])->name('edit');
    Route::put('/{kind}/{content}', [AdminController::class, 'update'])->name('update');
    Route::delete('/{kind}/{content}', [AdminController::class, 'destroy'])->name('destroy');
});
Route::get('/{section}', [PublicController::class, 'section'])->name('section');
Route::get('/{section}/{slug}', [PublicController::class, 'detail'])->name('detail');
