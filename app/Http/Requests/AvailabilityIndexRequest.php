<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Dto\Slot\SlotAvailabilityDto;

class AvailabilityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => 'nullable|string|in:id_asc,id_desc,remaining_asc,remaining_desc',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Преобразуем входные данные в DTO
     */
    public function toDto(): SlotAvailabilityDto
    {
        return new SlotAvailabilityDto(
            sort: $this->input('sort'),
            page: $this->input('page') ? (int)$this->input('page') : 1,
        );
    }
}
