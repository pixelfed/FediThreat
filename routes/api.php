<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ThreatCheckController;
use App\Http\Controllers\ApiController;

Route::post('/v1/report', [ReportController::class, 'store'])->middleware('validate.instance.key');
Route::get('/v1/check', [ThreatCheckController::class, 'check'])->middleware('validate.instance.key');
