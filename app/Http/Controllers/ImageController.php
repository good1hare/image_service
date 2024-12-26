<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use App\Jobs\UploadImageToS3;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImageController extends Controller
{
    public function uploadImage(UploadImageRequest $request, string $id): JsonResponse
    {
        // Получаем файл
        $image = $request->file('image');

        // Создаем путь для сохранения
        $path = "images/{$id}." . $image->getClientOriginalExtension();

        try {
            // Загружаем файл в S3
            $result = Storage::disk('s3')->put($path, file_get_contents($image));
            if (!$result) {
                throw new RuntimeException('Ошибка сохранения файла.');
            }

            return response()->json([
                'identifier' => $id,
                'message' => 'Файл успешно загружен в S3',
            ]);
        } catch (Exception $e) {
            // При ошибке ставим задачу в очередь
            $tempPath = $image->store('temp', 'local');

            UploadImageToS3::dispatch($tempPath, $path);

            Log::error('Файл поставлен в очередь для загрузки в S3', [
                'identifier' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'identifier' => $id,
                'message' => 'Файл временно не загружен, но будет обработан позже.',
            ], 201);
        }
    }

}
