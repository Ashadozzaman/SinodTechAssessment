<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by the `permission:` route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            // `mimes` alone (no `image` rule) — the `image` rule runs
            // getimagesize(), which rejects valid SVGs.
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
        ];
    }
}
