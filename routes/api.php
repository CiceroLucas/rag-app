<?php

use App\Http\Controllers\Api\DocumentAskController;
use App\Http\Controllers\Api\DocumentUploadController;
use Illuminate\Support\Facades\Route;

Route::post('/documents', [DocumentUploadController::class, 'store']);
Route::post('/ask', DocumentAskController::class);
