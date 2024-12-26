<?php

use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('upload-image/{id}', [ImageController::class, 'uploadImage'])
    ->where('id', '[a-zA-Z0-9\-]+'); // Только буквы, цифры и дефисы
