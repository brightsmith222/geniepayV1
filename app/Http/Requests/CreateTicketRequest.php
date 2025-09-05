<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
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
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Ticket subject is required.',
            'subject.max' => 'Ticket subject cannot exceed 255 characters.',
            'description.required' => 'Ticket description is required.',
            'description.max' => 'Ticket description cannot exceed 5000 characters.',
        ];
    }
}
