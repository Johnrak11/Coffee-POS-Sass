<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:shops,slug',
            'password' => 'required|min:4',
            'plan' => 'required',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => 'required|min:6',

            // Optional fields for creation
            'bakong_account_id' => 'nullable|string|max:255',
            'merchant_name' => 'nullable|string|max:255',
            'merchant_city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'logo_url' => 'nullable|url',
            'receipt_footer' => 'nullable|string|max:255',
            'primary_color' => 'nullable|string|max:7',
            'currency_symbol' => 'nullable|string|max:5',
            'bakong_wallet_id' => 'nullable|string|max:255',
            'theme_mode' => 'nullable|in:light,dark',
        ];
    }
}
