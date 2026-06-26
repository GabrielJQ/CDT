<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCasaPorCasaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'xlsx_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'trimestre' => ['required', 'in:T1,T2,T3,T4'],
            'fecha_corte' => ['nullable', 'date'],
        ];
    }
}
