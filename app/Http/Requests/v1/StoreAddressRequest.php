<?php

namespace App\Http\Requests\v1;

use App\Models\CrLocation;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:50', 'in:Casa,Trabajo,Otro'],
            'province' => ['required', 'string', 'max:100'],
            'canton' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address_details' => ['required', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ];
    }

    /**
     * Validación adicional: verificar que la ubicación existe en cr_locations
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Solo validar si los campos de ubicación están presentes
            if ($this->has(['province', 'canton', 'district'])) {
                $exists = CrLocation::locationExists(
                    $this->province,
                    $this->canton,
                    $this->district
                );

                if (!$exists) {
                    $validator->errors()->add(
                        'district',
                        'La combinación de Provincia, Cantón y Distrito no es válida en Costa Rica'
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados en español
     */
    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta es obligatoria',
            'label.in' => 'La etiqueta debe ser: Casa, Trabajo u Otro',
            'label.max' => 'La etiqueta no debe exceder 50 caracteres',

            'province.required' => 'La provincia es obligatoria',
            'province.max' => 'La provincia no debe exceder 100 caracteres',

            'canton.required' => 'El cantón es obligatorio',
            'canton.max' => 'El cantón no debe exceder 100 caracteres',

            'district.required' => 'El distrito es obligatorio',
            'district.max' => 'El distrito no debe exceder 100 caracteres',

            'address_details.required' => 'Las señas exactas son obligatorias',
            'address_details.max' => 'Las señas exactas no deben exceder 500 caracteres',

            'is_default.boolean' => 'El campo dirección predeterminada debe ser verdadero o falso',
        ];
    }
}
