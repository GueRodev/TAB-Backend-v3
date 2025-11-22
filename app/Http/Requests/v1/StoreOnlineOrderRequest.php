<?php

namespace App\Http\Requests\v1;

use App\Models\CrLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para crear pedidos online
 *
 * IMPORTANTE: Solo usuarios AUTENTICADOS pueden crear pedidos online
 *
 * OPCIONES DE DIRECCIÓN:
 * Opción A: Usar dirección guardada (address_id)
 * Opción B: Campos manuales con IDs (province_id, canton_id, district_id)
 * Opción C: Campos manuales con nombres (province, canton, district) - más flexible para frontend
 *
 * VALIDACIONES:
 * - Si usa address_id: debe existir y pertenecer al usuario
 * - Si usa campos manuales: deben existir en cr_locations
 */
class StoreOnlineOrderRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado
     * Solo usuarios autenticados pueden crear pedidos online
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            // Información del cliente (opcional, se toma del perfil si no se envía)
            'customer_name' => ['sometimes', 'required', 'string', 'max:100'],
            'customer_phone' => ['sometimes', 'required', 'string', 'max:20'],
            'customer_email' => ['sometimes', 'required', 'email', 'max:100'],

            // Opción de entrega
            'delivery_option' => ['required', 'in:pickup,delivery'],

            // OPCIÓN A: Dirección guardada (solo para delivery)
            // Si se proporciona, debe existir y pertenecer al usuario
            'address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id());
                })
            ],

            // OPCIÓN B/C: Campos manuales de dirección
            'shipping_address' => [
                'sometimes',
                'array'
            ],

            // Opción B: Con IDs (para selectores que envían IDs)
            'shipping_address.province_id' => [
                'sometimes',
                'integer',
                'exists:cr_locations,id,type,province'
            ],
            'shipping_address.canton_id' => [
                'sometimes',
                'integer',
                'exists:cr_locations,id,type,canton'
            ],
            'shipping_address.district_id' => [
                'sometimes',
                'integer',
                'exists:cr_locations,id,type,district'
            ],

            // Opción C: Con nombres (para selectores que envían nombres)
            'shipping_address.province' => [
                'sometimes',
                'string',
                'max:100'
            ],
            'shipping_address.canton' => [
                'sometimes',
                'string',
                'max:100'
            ],
            'shipping_address.district' => [
                'sometimes',
                'string',
                'max:100'
            ],

            // Dirección exacta (requerida si hay shipping_address)
            'shipping_address.address_details' => [
                'required_with:shipping_address',
                'string',
                'max:500'
            ],

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
     * Validaciones adicionales con withValidator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // VALIDACIÓN CRÍTICA: Para delivery, debe haber address_id O shipping_address
            if ($this->delivery_option === 'delivery') {
                if (!$this->address_id && !$this->has('shipping_address')) {
                    $validator->errors()->add(
                        'address',
                        'Para entregas a domicilio debe proporcionar address_id o shipping_address'
                    );
                }

                // VALIDACIÓN: No pueden venir AMBOS
                if ($this->address_id && $this->has('shipping_address')) {
                    $validator->errors()->add(
                        'address',
                        'No puede proporcionar address_id y shipping_address al mismo tiempo'
                    );
                }
            }

            // VALIDACIÓN: Verificar ubicaciones si son manuales
            if ($this->has('shipping_address') && !$this->address_id) {
                $this->validateShippingAddress($validator);
            }

            // VALIDACIÓN CRÍTICA: customer_phone es obligatorio si el usuario no lo tiene en perfil
            $user = auth()->user();
            if (!$user->phone && !$this->customer_phone) {
                $validator->errors()->add(
                    'customer_phone',
                    'Debe proporcionar un teléfono de contacto. Puede completarlo en su perfil o enviarlo en este pedido'
                );
            }
        });
    }

    /**
     * Valida la dirección de envío (ya sea con IDs o con nombres)
     */
    private function validateShippingAddress($validator)
    {
        $shippingAddress = $this->input('shipping_address', []);

        // Determinar si viene con IDs o con nombres
        $hasIds = isset($shippingAddress['province_id']) &&
                  isset($shippingAddress['canton_id']) &&
                  isset($shippingAddress['district_id']);

        $hasNames = isset($shippingAddress['province']) &&
                    isset($shippingAddress['canton']) &&
                    isset($shippingAddress['district']);

        // Debe tener al menos una de las dos opciones
        if (!$hasIds && !$hasNames) {
            $validator->errors()->add(
                'shipping_address',
                'Debe proporcionar provincia, cantón y distrito (ya sea por ID o por nombre)'
            );
            return;
        }

        // Validar según el formato recibido
        if ($hasIds) {
            $this->validateLocationHierarchyById($validator);
        } else if ($hasNames) {
            $this->validateLocationHierarchyByName($validator);
        }
    }

    /**
     * Valida jerarquía de ubicaciones usando IDs
     */
    private function validateLocationHierarchyById($validator)
    {
        $provinceId = $this->input('shipping_address.province_id');
        $cantonId = $this->input('shipping_address.canton_id');
        $districtId = $this->input('shipping_address.district_id');

        $province = $provinceId ? CrLocation::find($provinceId) : null;
        $canton = $cantonId ? CrLocation::find($cantonId) : null;
        $district = $districtId ? CrLocation::find($districtId) : null;

        // Verificar que el cantón pertenece a la provincia
        if ($province && $canton) {
            if ($canton->type !== 'canton' || $canton->province_id != $province->province_id) {
                $validator->errors()->add(
                    'shipping_address.canton_id',
                    'El cantón seleccionado no pertenece a la provincia'
                );
            }
        }

        // Verificar que el distrito pertenece al cantón
        if ($canton && $district) {
            if ($district->type !== 'district' || $district->canton_id != $canton->canton_id) {
                $validator->errors()->add(
                    'shipping_address.district_id',
                    'El distrito seleccionado no pertenece al cantón'
                );
            }
        }
    }

    /**
     * Valida jerarquía de ubicaciones usando nombres
     */
    private function validateLocationHierarchyByName($validator)
    {
        $provinceName = $this->input('shipping_address.province');
        $cantonName = $this->input('shipping_address.canton');
        $districtName = $this->input('shipping_address.district');

        // Verificar que la combinación existe en cr_locations
        $exists = CrLocation::locationExists($provinceName, $cantonName, $districtName);

        if (!$exists) {
            $validator->errors()->add(
                'shipping_address.district',
                'La combinación de Provincia, Cantón y Distrito no es válida en Costa Rica'
            );
        }
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es obligatorio',
            'customer_phone.required' => 'El teléfono del cliente es obligatorio',
            'customer_email.required' => 'El email del cliente es obligatorio',
            'customer_email.email' => 'El email debe ser válido',

            'delivery_option.required' => 'Debe especificar la opción de entrega',
            'delivery_option.in' => 'La opción de entrega debe ser pickup o delivery',

            'address_id.exists' => 'La dirección seleccionada no existe o no te pertenece',

            'shipping_address.province_id.exists' => 'La provincia seleccionada no es válida',
            'shipping_address.canton_id.exists' => 'El cantón seleccionado no es válido',
            'shipping_address.district_id.exists' => 'El distrito seleccionado no es válido',
            'shipping_address.address_details.required_with' => 'Los detalles de la dirección son obligatorios',

            'payment_method.required' => 'Debe especificar el método de pago',
            'payment_method.in' => 'El método de pago no es válido',

            'items.required' => 'Debe incluir al menos un producto',
            'items.*.product_id.exists' => 'Uno o más productos no existen',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1',
        ];
    }

    /**
     * Mensaje cuando falla la autorización
     */
    protected function failedAuthorization()
    {
        abort(401, 'Debe iniciar sesión para crear un pedido');
    }
}
