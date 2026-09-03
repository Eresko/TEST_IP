<?php

namespace App\Http\Requests;

use App\Dto\MockSupplierBuyDto;
use Illuminate\Foundation\Http\FormRequest;

class MockSupplierStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'partner_order_id' => 'required|uuid',
        ];
    }
    

}