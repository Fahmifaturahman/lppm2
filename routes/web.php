<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ProposalDownloadController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/proposals/{proposal}/download', [ProposalDownloadController::class, 'download'])
    ->middleware('auth') 
    ->name('proposals.download');
