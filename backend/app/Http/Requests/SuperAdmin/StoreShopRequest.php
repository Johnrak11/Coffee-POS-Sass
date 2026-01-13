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
            'name' => 'required',
            'slug' => 'required|unique:shops,slug',
            'password' => 'required|min:4',
            'plan' => 'required',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => 'required|min:6'
        ];
    }
}
