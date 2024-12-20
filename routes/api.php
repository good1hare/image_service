<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

Route::post('/upload-image', [ImageController::class, 'uploadImage']);

