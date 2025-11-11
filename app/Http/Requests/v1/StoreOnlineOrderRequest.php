<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnlineOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Los clientes autenticados pueden crear pedidos online
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
            // Información del cliente (obligatoria)
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['required', 'email', 'max:100'], // Obligatorio para online

            // Opción de entrega
            'delivery_option' => ['required', 'in:pickup,delivery'],

            // Dirección de envío (obligatoria solo si delivery_option es 'delivery')
            'shipping_address' => ['required_if:delivery_option,delivery', 'array'],
            'shipping_address.province' => ['required_if:delivery_option,delivery', 'string', 'max:100'],
            'shipping_address.canton' => ['required_if:delivery_option,delivery', 'string', 'max:100'],
            'shipping_address.district' => ['required_if:delivery_option,delivery', 'string', 'max:100'],
            'shipping_address.address_details' => ['required_if:delivery_option,delivery', 'string'],

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
            'customer_email.required' => 'El email del cliente es obligatorio para pedidos online',
            'customer_email.email' => 'El email debe ser válido',
            'delivery_option.required' => 'Debe especificar la opción de entrega',
            'delivery_option.in' => 'La opción de entrega debe ser pickup o delivery',
            'shipping_address.required_if' => 'La dirección de envío es obligatoria para entregas a domicilio',
            'payment_method.required' => 'Debe especificar el método de pago',
            'payment_method.in' => 'El método de pago no es válido',
            'items.required' => 'Debe incluir al menos un producto',
            'items.*.product_id.exists' => 'Uno o más productos no existen',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1',
        ];
    }
}
