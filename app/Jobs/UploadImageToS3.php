<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UploadImageToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $tempPath;
    public string $path_s3;

    /**
     * Интервалы задержек перед повторной попыткой (в секундах):
     * 10 секунд, 1 минута, 10 минут, 30 минут, 2 часа, 6 часов, 24 часа.
     */
    public array $backoff = [10, 60, 600, 1800, 7200, 21600, 86400];

    /**
     * Максимальное количество попыток.
     */
    public int $tries = 7;

    /**
     * Create a new job instance.
     *
     * @param string $tempPath
     * @param string $path_s3
     */
    public function __construct(string $tempPath, string $path_s3)
    {
        $this->tempPath = $tempPath;
        $this->path_s3 = $path_s3;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            if (!Storage::disk('local')->exists($this->tempPath)) {
                Log::error('Файл не существует в локальном хранилище', ['path' => $this->tempPath]);
                return;
            }

            // Загружаем файл из временной директории в minio
            $content = file_get_contents(storage_path('app/private/' . $this->tempPath));

            $result = Storage::disk('s3')->put($this->path_s3, $content);

            if (!$result) {
                throw new RuntimeException('Ошибка сохранения файла.');
            }

            // Удаляем файл из временной директории после успешной загрузки
            Storage::disk('local')->delete($this->tempPath);

            Log::info('Файл успешно загружен в S3', ['path' => $this->path_s3]);
        } catch (Exception $e) {
            Log::error('Ошибка при загрузке файла в S3 через очередь', [
                'message' => $e->getMessage(),
                'path' => $this->tempPath,
            ]);

            throw $e;
        }
    }

    /**
     * Обработчик для проваленных заданий.
     *
     * @param Exception $exception
     *
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('Задание провалено', ['message' => $exception->getMessage()]);
    }
}
