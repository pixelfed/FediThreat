<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy.html', function () {
    return view('privacy');
});

Route::get('/provider_info', [ApiController::class, 'faspProviderInfo']);
