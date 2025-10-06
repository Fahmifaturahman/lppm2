<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/download/{file}', function ($file) {
    $path = 'proposals/' . $file;

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response()->download(storage_path("app/public/{$path}"));
})->name('download-proposal');
