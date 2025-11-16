<?php

namespace App\Http\Requests\v1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para actualizar usuarios (Super Admin y Moderador)
 *
 * REGLAS DE NEGOCIO:
 * - Solo Super Admin puede actualizar usuarios
 * - No se puede cambiar el rol del último Super Admin
 * - Un usuario no puede cambiar su propio rol
 * - NO permite actualizar usuarios Cliente (solo Admin y Moderador)
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta petición
     * Solo los Super Admin pueden actualizar usuarios
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole('Super Admin');
    }

    /**
     * Reglas de validación
     *
     * NOTA: Todos los campos son opcionales (sometimes) porque
     * solo se actualiza lo que se envía en el request
     */
    public function rules(): array
    {
        // ID del usuario que se está editando (viene de la ruta)
        $userId = $this->route('user');

        return [
            // Nombre: opcional, pero si se envía debe ser texto de máximo 255 caracteres
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            // Email: opcional, pero si se envía debe ser único (excepto para este usuario)
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId) // Ignora el email del usuario actual
            ],

            // Password: opcional, si se envía debe tener mínimo 8 caracteres y confirmación
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],

            // Rol: opcional, pero si se envía debe ser Super Admin o Moderador
            'role' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['Super Admin', 'Moderador']), // Solo estos roles permitidos

                // VALIDACIÓN ESPECIAL: Prohibir cambiar propio rol
                // Si el ID del usuario a editar es igual al ID del usuario autenticado
                Rule::when(
                    $this->route('user') == auth()->id(), // ¿Es el mismo usuario?
                    fn() => Rule::prohibitedIf(true)->__toString(), // Sí → Prohibir cambio
                    fn() => [] // No → Permitir cambio
                ),
            ],
        ];
    }

    /**
     * Validaciones adicionales que requieren consultas a la base de datos
     *
     * Se ejecuta DESPUÉS de las validaciones básicas
     * Solo valida si se está intentando cambiar el rol
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Si no se está cambiando el rol, no hacer validaciones adicionales
            if (!$this->shouldValidateRoleChange()) {
                return;
            }

            // Obtener el usuario que se está editando
            $user = User::findOrFail($this->route('user'));

            // VALIDACIÓN CRÍTICA: No permitir cambiar el rol del último Super Admin
            // Esto evita que el sistema quede sin administradores
            if ($this->isLastSuperAdmin($user)) {
                $validator->errors()->add(
                    'role',
                    'No se puede cambiar el rol del último Super Admin del sistema'
                );
            }
        });
    }

    /**
     * Verifica si se debe validar el cambio de rol
     *
     * Solo valida si:
     * 1. Se está enviando el campo 'role' en el request
     * 2. El rol nuevo es diferente al rol actual del usuario
     *
     * @return bool
     */
    private function shouldValidateRoleChange(): bool
    {
        // Si no se envió el campo 'role', no validar
        if (!$this->has('role')) {
            return false;
        }

        // Obtener el usuario que se está editando
        $user = User::find($this->route('user'));

        // Si el usuario no existe o el rol no cambió, no validar
        return $user && $this->role !== $user->getRoleNames()->first();
    }

    /**
     * Verifica si el usuario es el último Super Admin del sistema
     *
     * @param User $user Usuario a verificar
     * @return bool True si es el último Super Admin
     */
    private function isLastSuperAdmin(User $user): bool
    {
        // Verifica dos condiciones:
        // 1. El usuario tiene rol Super Admin
        // 2. Solo hay 1 Super Admin en el sistema
        return $user->hasRole('Super Admin') &&
               User::role('Super Admin')->count() <= 1;
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

            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',

            'role.required' => 'El rol es obligatorio',
            'role.in' => 'El rol debe ser Super Admin o Moderador',
            'role.prohibited_if' => 'No puedes cambiar tu propio rol',
        ];
    }

    /**
     * Mensaje personalizado cuando falla la autorización
     */
    protected function failedAuthorization()
    {
        abort(403, 'Solo los Super Admin pueden actualizar usuarios');
    }
}
