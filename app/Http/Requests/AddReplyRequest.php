<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddReplyRequest extends FormRequest
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
            'message' => 'required|string|max:5000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Reply message is required.',
            'message.max' => 'Reply message cannot exceed 5000 characters.',
        ];
    }
}
