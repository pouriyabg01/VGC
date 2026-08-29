<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller authorizes against GamePolicy; this only shapes input.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:255'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.mimes' => 'The cover must be a JPG, PNG or WEBP image.',
            'image.max' => 'The cover must be 2 MB or smaller.',
            // PHP drops anything over its own limit before validation runs, so
            // the generic failure needs to say what actually went wrong.
            'image.uploaded' => 'The cover was too large for the server to accept. Keep it under 2 MB.',
        ];
    }
}
