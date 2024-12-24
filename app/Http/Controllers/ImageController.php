<?php

namespace App\Http\Controllers;

use App\Jobs\UploadImageToS3;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

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

        try {
            // Загрузка файла в minio
            $result = Storage::disk('s3')->put($path, file_get_contents($image));
            if (!$result) {
                throw new RuntimeException('Ошибка сохранения файла.');
            }

            $url = Storage::disk('s3')->url($path);

            return response()->json([
                'identifier' => $identifier,
                'url' => $url,
            ]);
        } catch (Exception $e) {
            // Если ошибка, сохраняем файл во временную директорию и ставим задачу в очередь
            $tempPath = $image->store('temp', 'local');

            UploadImageToS3::dispatch($tempPath, $path);

            Log::error('Файл поставлен в очередь для загрузки в S3', [
                'message' => $e->getMessage(),
                'path' => $tempPath,
            ]);

            return response()->json([
                'identifier' => $identifier,
                'message' => 'Файл временно не загружен, но будет обработан позже.',
                'path' => $tempPath,
            ], 202);
        }
    }
}

