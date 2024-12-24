<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadImageToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tempPath;
    public $path_s3;
    public $tries = 5;
    public $backoff = [30, 60, 90, 120, 150]; // задержки на 1, 2 и 3 попытки

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
     */
    public function handle()
    {
        try {
            if (is_null($this->tempPath)) {
                Log::error('Не задан путь для временного файла', ['tempPath' => $this->tempPath]);
                return;
            }

            if (!Storage::disk('local')->exists($this->tempPath)) {
                Log::error('Файл не существует в локальном хранилище', ['path' => $this->tempPath]);
                return;
            }

            // Загружаем файл из временной директории в S3
            $content = file_get_contents(storage_path('app/private/' . $this->tempPath));

            if (empty($this->path_s3)) {
                Log::error('Путь для S3 не задан!');
            }

            $result = Storage::disk('s3')->put($this->path_s3, $content);

            if (!$result) {
                throw new \RuntimeException('Ошибка сохранения файла. Результат: false.');
            }

            // Удаляем файл из временной директории после успешной загрузки
            Storage::delete('private/' . $this->tempPath);

            Log::info('Файл успешно загружен в S3', ['path' => $this->path_s3]);
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке файла в S3 через очередь', [
                'message' => $e->getMessage(),
                'path' => 'private/' . $this->tempPath,
            ]);

            // Вы можете добавить повторный запуск задания
            $this->release(30); // Повтор через 10 секунд
        }
    }
}
