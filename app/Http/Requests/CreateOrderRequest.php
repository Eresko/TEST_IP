<?php

namespace App\Http\Requests;

use App\Dto\CreateOrderDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge($this->all(), [
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku'             => 'required|string|max:50',
            'idempotency_key' => 'required|string|max:255',
        ];
    }


    /**
     * @return CreateOrderDto
     */

    public function toDto(): CreateOrderDto
    {
        return new CreateOrderDto(
            sku: $this->input('sku'),
            idempotencyKey: $this->header('Idempotency-Key')
        );
    }

}
