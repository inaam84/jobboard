<?php

use App\Http\Controllers\PostingController;
use Illuminate\Support\Facades\Route;


Route::get('/', [PostingController::class, 'index'])
    ->name('postings.index');

Route::get('/create', [PostingController::class, 'create'])
    ->name('postings.create');

Route::post('/create', [PostingController::class, 'store'])
    ->name('postings.store');

Route::get('/dashboard', function () {
    $postings = auth()->user()->postings;

    return view('dashboard', compact('postings'));
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

Route::get('/{posting}', [PostingController::class, 'show'])
    ->name('postings.show');

Route::get('/{posting}/apply', [PostingController::class, 'apply'])
    ->name('postings.apply');
