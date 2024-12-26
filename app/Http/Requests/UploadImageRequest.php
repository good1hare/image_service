<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для выполнения этого запроса.
     */
    public function authorize(): bool
    {
        return true; // Если требуется авторизация, можно добавить проверку.
    }

    /**
     * Правила валидации для запроса.
     */
    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:jpg,jpeg,png|max:5120', // Максимальный размер 5MB
        ];
    }

    /**
     * Сообщения об ошибках валидации.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Файл изображения обязателен.',
            'image.file' => 'Должен быть загружен файл.',
            'image.mimes' => 'Файл должен быть формата JPG, JPEG или PNG.',
            'image.max' => 'Файл не должен превышать 5MB.',
        ];
    }
}
