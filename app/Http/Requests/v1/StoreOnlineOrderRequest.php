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
 * Opción B: Campos manuales (province_id, canton_id, district_id, address_details)
 *
 * VALIDACIONES:
 * - Si usa address_id: debe existir y pertenecer al usuario
 * - Si usa campos manuales: deben existir en cr_locations y respetar jerarquía
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

            // OPCIÓN B: Campos manuales de dirección
            // Opcional: Solo se valida si está presente
            // La validación de que debe existir (para delivery sin address_id) está en withValidator()
            'shipping_address' => [
                'sometimes',
                'array'
            ],
            'shipping_address.province_id' => [
                'required_with:shipping_address',
                'integer',
                'exists:cr_locations,id,type,province'
            ],
            'shipping_address.canton_id' => [
                'required_with:shipping_address',
                'integer',
                'exists:cr_locations,id,type,canton'
            ],
            'shipping_address.district_id' => [
                'required_with:shipping_address',
                'integer',
                'exists:cr_locations,id,type,district'
            ],
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

            // VALIDACIÓN: Verificar jerarquía de ubicaciones si son manuales
            if ($this->has('shipping_address') && !$this->address_id) {
                $this->validateLocationHierarchy($validator);
            }

            // VALIDACIÓN CRÍTICA: customer_phone es obligatorio si el usuario no lo tiene en perfil
            // FILOSOFÍA: El teléfono solo se valida cuando se necesita (al crear pedido)
            // - NO se pide en el registro (mejor UX)
            // - El usuario lo completa en su perfil cuando quiera
            // - Al crear pedido, debe haber teléfono disponible (perfil o request)
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
     * Valida que canton pertenezca a province y district pertenezca a canton
     */
    private function validateLocationHierarchy($validator)
    {
        $provinceId = $this->input('shipping_address.province_id');
        $cantonId = $this->input('shipping_address.canton_id');
        $districtId = $this->input('shipping_address.district_id');

        // Obtener los registros
        $province = $provinceId ? CrLocation::find($provinceId) : null;
        $canton = $cantonId ? CrLocation::find($cantonId) : null;
        $district = $districtId ? CrLocation::find($districtId) : null;

        // Verificar que el cantón pertenece a la provincia
        // Comparamos las columnas province_id de ambos registros
        if ($province && $canton) {
            // Verificar que el cantón existe, es de tipo canton, y pertenece a la provincia
            if ($canton->type !== 'canton' || $canton->province_id != $province->province_id) {
                $validator->errors()->add(
                    'shipping_address.canton_id',
                    'El cantón seleccionado no pertenece a la provincia'
                );
            }
        }

        // Verificar que el distrito pertenece al cantón
        // Comparamos las columnas canton_id de ambos registros
        if ($canton && $district) {
            // Verificar que el distrito existe, es de tipo district, y pertenece al cantón
            if ($district->type !== 'district' || $district->canton_id != $canton->canton_id) {
                $validator->errors()->add(
                    'shipping_address.district_id',
                    'El distrito seleccionado no pertenece al cantón'
                );
            }
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

            'shipping_address.required_if' => 'La dirección de envío es obligatoria para entregas a domicilio',
            'shipping_address.required_without' => 'Debe proporcionar una dirección si no usa address_id',
            'shipping_address.province_id.required_without' => 'Debe seleccionar una provincia',
            'shipping_address.province_id.exists' => 'La provincia seleccionada no es válida',
            'shipping_address.canton_id.required_without' => 'Debe seleccionar un cantón',
            'shipping_address.canton_id.exists' => 'El cantón seleccionado no es válido',
            'shipping_address.district_id.required_without' => 'Debe seleccionar un distrito',
            'shipping_address.district_id.exists' => 'El distrito seleccionado no es válido',
            'shipping_address.address_details.required_without' => 'Los detalles de la dirección son obligatorios',

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
