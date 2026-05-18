<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum; // ДОБАВЛЕНО
use App\Enums\StatusMessage; // ДОБАВЛЕНО

class NotificationUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', new Enum(StatusMessage::class)],
        ];
    }
}
