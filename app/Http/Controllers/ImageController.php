<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function uploadImage(Request $request): JsonResponse
    {
        // Проверяем наличие файла
        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return response()->json(['error' => 'Invalid image file'], 400);
        }

        // Получаем файл
        $image = $request->file('image');

        // Генерируем уникальный идентификатор
        $identifier = Str::uuid()->toString();

        // Создаем путь для сохранения
        $path = "images/{$identifier}." . $image->getClientOriginalExtension();

        // Сохраняем файл в MinIO
        Storage::disk('s3')->put($path, file_get_contents($image));

        // Возвращаем идентификатор и URL сохранённой картинки
        $url = Storage::disk('s3')->url($path);

        return response()->json([
            'identifier' => $identifier,
            'url' => $url,
        ]);
    }
}

