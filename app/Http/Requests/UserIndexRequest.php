<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Dto\User\UserIndexDto;

class UserIndexRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'    => 'nullable|string|max:255',
            'sort'    => 'nullable|string|in:id_asc,id_desc,name_asc,name_desc',
            'page'    => 'nullable|numeric|min:1',
        ];
    }

    /**
     * Преобразуем входные данные в DTO
     */
    public function toDto(): UserIndexDto
    {
        return new UserIndexDto(
            name: $this->validated('name'),
            sort: $this->input('sort') ?? null,
            page: $this->input('page') ?? null,
        );
    }
}