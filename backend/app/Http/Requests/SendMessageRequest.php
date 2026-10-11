<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:10000', 'required_without:file'],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
            'file' => [
                'nullable',
                'file',
                'max:102400', // 100 MiB
                'required_without:body',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => 'Please provide a message or a file.',
            'file.required_without' => 'Please provide a message or a file.',
            'file.max' => 'The file size cannot exceed 100 MiB.',
        ];
    }
}
