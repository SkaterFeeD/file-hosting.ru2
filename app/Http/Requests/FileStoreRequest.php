<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileStoreRequest extends ApiRequest
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
            'files.*' => 'required|file|max:2048|mimes:doc,pdf,docx,zip,jpeg,jpg,png',
        ];
    }

    public function messages()
    {
        return [
            'files.*.required' => 'Each file is required.',
            'files.*.file' => 'Each file must be a valid file.',
            'files.*.max' => 'Each file may not be greater than 2048 kilobytes.',
            'files.*.mimes' => 'Each file must be of type: doc, pdf, docx, zip, jpeg, jpg, png.',
        ];
    }
}
