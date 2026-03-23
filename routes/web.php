<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 兼容生产环境的logout GET请求
Route::get('/admin/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/admin/login');
})->name('filament.admin.auth.logout.get');
