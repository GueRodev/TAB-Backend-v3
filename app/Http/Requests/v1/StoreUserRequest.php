<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para crear nuevos usuarios (Super Admin y Moderador)
 *
 * REGLAS DE NEGOCIO:
 * - Solo Super Admin puede crear usuarios
 * - Solo permite crear Super Admin o Moderador (NO Cliente)
 * - Los clientes se registran ellos mismos vía /register
 * - Email debe ser único en el sistema
 * - Password requiere confirmación
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta petición
     * Solo los Super Admin pueden crear usuarios
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    /**
     * Reglas de validación
     *
     * Todos los campos son OBLIGATORIOS al crear un usuario
     */
    public function rules(): array
    {
        return [
            // Nombre completo del usuario
            'name' => ['required', 'string', 'max:255'],

            // Email único en todo el sistema
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email' // No puede existir en la tabla users
            ],

            // Password con confirmación obligatoria (password_confirmation)
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed' // Requiere campo password_confirmation
            ],

            // Rol: SOLO puede ser Super Admin o Moderador
            // Los usuarios Cliente se registran por su cuenta
            'role' => [
                'required',
                'string',
                Rule::in(['Super Admin', 'Moderador'])
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.max' => 'El nombre no debe exceder 255 caracteres',

            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'El correo electrónico debe ser válido',
            'email.unique' => 'Este correo electrónico ya está registrado',
            'email.max' => 'El correo electrónico no debe exceder 255 caracteres',

            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',

            'role.required' => 'El rol es obligatorio',
            'role.in' => 'Solo se pueden crear usuarios Super Admin o Moderador',
        ];
    }

    /**
     * Mensaje de autorización denegada
     */
    protected function failedAuthorization()
    {
        abort(403, 'Solo los Super Admin pueden crear usuarios');
    }
}
