<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoutineImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'pdf_file.required' => 'A PDF file is required.',
            'pdf_file.file' => 'The uploaded file must be a valid file.',
            'pdf_file.mimes' => 'The file must be a PDF.',
            'pdf_file.max' => 'The file size must not exceed 10MB.',
        ];
    }
}
