<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class StoreInStoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo Super Admin puede crear pedidos en tienda
        return $this->user()->hasRole('Super Admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Información del cliente (obligatoria)
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'], // Opcional para in_store

            // Método de pago
            'payment_method' => ['required', 'in:cash,card,transfer,sinpe'],

            // Items del pedido
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            // Notas opcionales
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es obligatorio',
            'customer_phone.required' => 'El teléfono del cliente es obligatorio',
            'customer_email.email' => 'El email debe ser válido',
            'payment_method.required' => 'Debe especificar el método de pago',
            'payment_method.in' => 'El método de pago no es válido',
            'items.required' => 'Debe incluir al menos un producto',
            'items.*.product_id.exists' => 'Uno o más productos no existen',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1',
        ];
    }
}
