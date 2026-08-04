<?php

namespace App\Http\Requests;

use App\Dto\Slot\CreateHoldDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateHoldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:slots,id',
        ];
    }

    /**
     * Валидируем заголовок Idempotency-Key перед обработкой
     */
    public function prepareForValidation()
    {
        $idempotencyKey = $this->header('Idempotency-Key');
        $this->merge([
            'id' => $this->route('id') ? (int) $this->route('id') : null,
        ]);
        if (!$idempotencyKey || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idempotencyKey)) {
            abort(400, 'Заголовок Idempotency-Key обязателен и должен быть валидным UUID.');
        }
    }

    public function toDto(): CreateHoldDto
    {
        return new CreateHoldDto(
            slotId: (int)$this->route('id'),
            idempotencyKey: $this->header('Idempotency-Key')
        );
    }

}
