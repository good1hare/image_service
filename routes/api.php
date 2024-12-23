<?php

use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/upload-image', [ImageController::class, 'uploadImage']);

Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});
