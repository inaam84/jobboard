<?php

use App\Http\Controllers\PostingController;
use Illuminate\Support\Facades\Route;


Route::get('/', [PostingController::class, 'index'])
    ->name('postings.index');

Route::get('/create', [PostingController::class, 'create'])
    ->name('postings.create');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';
