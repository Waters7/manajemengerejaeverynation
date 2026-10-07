<?php

namespace App\Http\Requests\Site;

use App\Enums\FulfillmentMethod;
use App\Enums\PaymentMethod;
use App\Rules\IndonesianPhone;
use App\Services\Settings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $settings = app(Settings::class);

        $fulfillments = [FulfillmentMethod::Pickup->value];
        if ($settings->enabled('store_delivery_enabled')) {
            $fulfillments[] = FulfillmentMethod::Delivery->value;
        }

        $payments = [PaymentMethod::BankTransfer->value];
        if ($settings->enabled('store_pay_on_pickup') && $this->input('fulfillment') === FulfillmentMethod::Pickup->value) {
            $payments[] = PaymentMethod::PayOnPickup->value;
        }

        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', new IndonesianPhone],
            'email' => ['nullable', 'email', 'max:190'],
            'fulfillment' => ['required', Rule::in($fulfillments)],
            'shipping_address' => ['nullable', 'required_if:fulfillment,delivery', 'string', 'max:1000'],
            'shipping_area' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in($payments)],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shipping_address.required_if' => 'Alamat pengiriman wajib diisi untuk pesanan yang dikirim.',
            'payment_method.in' => 'Metode pembayaran ini tidak tersedia untuk pilihan pengambilan tersebut.',
        ];
    }
}
