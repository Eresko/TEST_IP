<?php

namespace App\Http\Requests;

use App\Dto\WebhookDto;
use Illuminate\Foundation\Http\FormRequest;

class WebHookRequest extends FormRequest
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
            'order_id' => 'required|string|max:255',
            'event_id' => 'required|string|max:255', 
            'status'   => 'required|string|in:paid,failed',
            'amount'   => 'required|numeric',
        ];
    }

    /**
     * @return WebhookDto
     */
    public function toDto():WebhookDto {
        return new WebhookDto(
            orderId: $this->input('order_id'),
            paymentId: $this->input('event_id'),
        );
    }

}
