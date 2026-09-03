<?php

namespace App\Http\Requests;

use App\Dto\MockSupplierBuyDto;
use Illuminate\Foundation\Http\FormRequest;

class MockSupplierBuyRequest extends FormRequest
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
            'sku'              => 'required|string|max:50',
        ];
    }
    
    public function toDto(): MockSupplierBuyDto
    {

        return new MockSupplierBuyDto(
            name: (string) $this->route('name'),
            partnerOrderId: (string) $this->input('partner_order_id'),
            sku: (string) $this->input('sku')
        );
    }

}
