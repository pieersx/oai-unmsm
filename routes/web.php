<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/oai-pmh', [\App\Http\Controllers\OaiPmhController::class, 'handle'])->name('oai.pmh');
