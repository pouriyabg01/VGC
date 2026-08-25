<?php

namespace App\Http\Requests;

use App\Enums\Platforms\PlatformEnum;
use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class StorePlatformRequest extends FormRequest
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
            'nickname' => ['required' , 'string' , 'max:50'],
            'platform' => ['required' , 'string' , Rule::enum(PlatformEnum::class) , 'unique:platforms,platform']
        ];
    }
}
