<?php

namespace App\Http\Controllers;

use Aws\S3\Exception\S3Exception;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        try {
            $result = Storage::disk('s3')->put($path, file_get_contents($image));
            if (!$result) {
                throw new \RuntimeException('Ошибка сохранения файла. Результат: false.');
            }
        } catch (S3Exception $e) {
            Log::error('S3 ошибка при сохранении файла', [
                'error_message' => $e->getMessage(),
                'aws_request_id' => $e->getAwsRequestId(),
                'aws_error_code' => $e->getAwsErrorCode(),
                'aws_error_type' => $e->getAwsErrorType(),
            ]);
            return response()->json(['error' => 'Ошибка сохранения файла в S3'], 500);
        } catch (\Exception $e) {
            Log::error('Общая ошибка при сохранении файла', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Общая ошибка при сохранении файла'], 500);
        }

        // Возвращаем идентификатор и URL сохранённой картинки
        $url = Storage::disk('s3')->url($path);

        return response()->json([
            'identifier' => $identifier,
            'url' => $url,
            'result' => $result
        ]);
    }
}

