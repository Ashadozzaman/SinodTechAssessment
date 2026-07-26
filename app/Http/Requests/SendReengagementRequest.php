<?php

namespace App\Http\Requests;

use App\Enums\EngagementChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendReengagementRequest extends FormRequest
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
            'channel' => ['required', 'string', Rule::in(array_column(EngagementChannel::cases(), 'value'))],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }
}
