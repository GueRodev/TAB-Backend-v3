<?php

namespace App\Http\Requests\v1;

use App\Models\CrLocation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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
            'label' => ['sometimes', 'required', 'string', 'max:50', 'in:Casa,Trabajo,Otro'],
            'province' => ['sometimes', 'required', 'string', 'max:100'],
            'canton' => ['sometimes', 'required', 'string', 'max:100'],
            'district' => ['sometimes', 'required', 'string', 'max:100'],
            'address_details' => ['sometimes', 'required', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Validación adicional: verificar que la ubicación existe en cr_locations
     * Solo valida si se están actualizando campos de ubicación
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Obtener la dirección actual usando el ID de la ruta
            $addressId = $this->route('address');
            $address = \App\Models\Address::findOrFail($addressId);

            // Preparar valores para validación (usar nuevos valores o mantener existentes)
            $province = $this->input('province', $address->province);
            $canton = $this->input('canton', $address->canton);
            $district = $this->input('district', $address->district);

            // Validar combinación completa si algún campo de ubicación está presente
            if ($this->hasAny(['province', 'canton', 'district'])) {
                $exists = CrLocation::locationExists($province, $canton, $district);

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
