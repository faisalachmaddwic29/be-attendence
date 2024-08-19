<?php

use App\Livewire\Absen;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/login', function () {
    return redirect('admin/login');
})->name('login');


Route::group(['middleware' => 'auth'], function () {
    Route::get('absen', Absen::class)->name('absen');
});
