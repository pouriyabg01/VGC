<?php

namespace App\Http\Requests;

use App\Enums\Platforms\PlatformEnum;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform' => [
                'required',
                'string',
                Rule::unique('platforms', 'platform')
                    ->ignore($this->route('platform'))
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
                Rule::enum(PlatformEnum::class)
            ],
            'nickname' => 'required|string|min:3|max:50',
        ];
    }
}
