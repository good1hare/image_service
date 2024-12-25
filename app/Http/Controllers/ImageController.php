<?php

namespace App\Http\Controllers;

use App\Jobs\UploadImageToS3;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImageController extends Controller
{
    /**
     * Загрузка изображения с клиентским id.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function uploadImage(Request $request, string $id): JsonResponse
    {
        // Проверяем, что id передан и не пустой
        if (empty($id)) {
            return response()->json(['error' => 'ID не может быть пустым'], 400);
        }

        // Проверяем наличие файла
        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return response()->json(['error' => 'Некорректный файл изображения'], 400);
        }

        // Получаем файл
        $image = $request->file('image');

        // Создаем путь для сохранения
        $path = "images/{$id}." . $image->getClientOriginalExtension();

        try {
            // Загрузка файла в minio
            $result = Storage::disk('s3')->put($path, file_get_contents($image));
            if (!$result) {
                throw new RuntimeException('Ошибка сохранения файла.');
            }

            // Генерируем URL файла
            $url = Storage::disk('s3')->url($path);

            return response()->json([
                'id' => $id,
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
                'id' => $id,
                'message' => 'Файл временно не загружен, но будет обработан позже.',
            ], 202);
        }
    }
}
