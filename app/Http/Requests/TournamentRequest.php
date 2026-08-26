<?php

namespace App\Http\Requests;

use App\Enums\Platforms\PlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TournamentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'platform' => ['required', 'string', Rule::enum(PlatformEnum::class)],
            'game' => 'required|string|max:40',
            // capacity is NOT NULL, and the bracket only resolves cleanly from a
            // power of two — the same rule the Filament form applies.
            'capacity' => ['required', 'integer', 'min:2', function ($attribute, $value, $fail) {
                $n = (int) $value;
                if ($n <= 0 || ($n & ($n - 1)) !== 0) {
                    $fail('The capacity must be a power of 2 (e.g., 2, 4, 8, 16).');
                }
            }],
        ];
    }
}
