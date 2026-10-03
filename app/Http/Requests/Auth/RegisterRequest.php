<?php

namespace App\Http\Requests\Auth;

use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['required', 'string', 'max:50'],
            'whatsapp' => ['required', 'string', new IndonesianPhone],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama lengkap', 'nickname' => 'nama panggilan', 'whatsapp' => 'nomor WhatsApp'];
    }
}
