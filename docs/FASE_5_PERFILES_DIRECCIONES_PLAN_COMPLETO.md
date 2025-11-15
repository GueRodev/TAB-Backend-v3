# FASE 5: GESTIÓN DE PERFILES Y DIRECCIONES - PLAN DE IMPLEMENTACIÓN COMPLETO

**Proyecto:** TAB-Backend-v3 (Toys and Bricks)
**Framework:** Laravel 12 + PostgreSQL
**Fecha:** 2025-11-15
**Estado:** 📋 En Planificación

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Análisis del Sistema](#análisis-del-sistema)
3. [Estructura de Base de Datos](#estructura-de-base-de-datos)
4. [Plan de Implementación Detallado](#plan-de-implementación-detallado)
5. [Endpoints de API](#endpoints-de-api)
6. [Rutas y Controladores](#rutas-y-controladores)
7. [Seguridad y Validaciones](#seguridad-y-validaciones)
8. [Integración con Pedidos](#integración-con-pedidos)
9. [Testing y Validación](#testing-y-validación)
10. [Notas Importantes](#notas-importantes)

---

## 📊 RESUMEN EJECUTIVO

### Objetivo de la Fase 5

Implementar la gestión completa de perfiles de usuario (Admin y Cliente), un sistema de direcciones guardadas para clientes, y cargar datos territoriales de Costa Rica para uso en formularios de direcciones.

### Características Principales

✅ **Gestión de Perfiles Diferenciados:**
- **Admin:** Puede editar nombre, email y contraseña (teléfono opcional)
- **Cliente:** Puede editar nombre, email, teléfono (obligatorio) y contraseña

✅ **Sistema de Direcciones:**
- **Clientes:** CRUD completo de sus propias direcciones
- **Admin:** Puede VER todas las direcciones de todos los usuarios (solo lectura)
- Estructura de Costa Rica: Provincia > Cantón > Distrito
- Etiquetas personalizadas: Casa, Trabajo, Otro
- Dirección exacta (señas detalladas)
- Marcar dirección como predeterminada

✅ **Datos Territoriales de Costa Rica:**
- Tabla con 7 provincias, ~130 cantones, cientos de distritos
- Endpoint público para obtener jerarquía completa
- Validación server-side de ubicaciones reales
- Dropdowns en cascada en el frontend

✅ **Integración con Pedidos:**
- Pre-llenado de datos de usuario en checkout
- Selección de dirección guardada en pedidos con envío
- Snapshot inmutable en `order_shipping_addresses`

✅ **Validaciones Inteligentes:**
- Validación condicional según rol (phone obligatorio solo para clientes)
- Validación de unicidad de email
- Validación de contraseña solo si se proporciona
- Validación de ubicaciones reales de Costa Rica
- Usuarios solo pueden gestionar sus propios recursos
- Admin puede ver todas las direcciones (solo lectura)

---

## 🔍 ANÁLISIS DEL SISTEMA

### Contexto del Proyecto

**Fases Completadas:**
- ✅ Fase 1: Configuración Base Laravel 12
- ✅ Fase 2: Autenticación y Roles (Spatie Permissions)
- ✅ Fase 3: Productos y Categorías
- ✅ Fase 4: Sistema de Pedidos (con order_shipping_addresses)

**Estado Actual:**
- Tabla `users` existe con campos básicos: id, name, email, password
- Tabla `order_shipping_addresses` ya implementada (snapshots de pedidos)
- Sistema de roles funcionando: Super Admin, Cliente
- Middleware de autorización configurado
- Archivo `docs/cr_shipping_data.json` con datos territoriales de Costa Rica

**Permisos ya definidos en RolePermissionSeeder:**
```php
// Permisos de Perfil
Permission::create(['name' => 'view own profile']);
Permission::create(['name' => 'edit own profile']);

// Permisos de Direcciones
Permission::create(['name' => 'view all addresses']);      // Admin: Ver todas
Permission::create(['name' => 'manage own addresses']);    // Cliente: CRUD propias
```

**Asignación de permisos:**
- **Super Admin:** Todos los permisos (incluyendo `view all addresses`)
- **Cliente:** Solo `manage own addresses`

---

## 🗂️ ESTRUCTURA DE BASE DE DATOS

### 5.1: Modificación de Tabla `users`

**Migración:** `add_phone_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');

            // Índice para búsquedas
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropColumn('phone');
        });
    }
};
```

**Campos de `users` después de la modificación:**
- `id` (bigInteger, PK)
- `name` (string)
- `email` (string, unique)
- `phone` (string, nullable) ⬅️ NUEVO
- `email_verified_at` (timestamp, nullable)
- `password` (string)
- `remember_token` (string, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

---

### 5.2: Nueva Tabla `addresses`

**Migración:** `create_addresses_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // Relación con usuario (1:N)
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Etiqueta personalizada
            $table->string('label', 50)
                  ->comment('Casa, Trabajo, Otro');

            // Estructura de Costa Rica
            $table->string('province', 100)
                  ->comment('Provincia de Costa Rica');

            $table->string('canton', 100)
                  ->comment('Cantón de la provincia');

            $table->string('district', 100)
                  ->comment('Distrito del cantón');

            // Dirección específica
            $table->text('address_details')
                  ->comment('Señas exactas: calle, número, referencias');

            // Dirección predeterminada
            $table->boolean('is_default')
                  ->default(false)
                  ->comment('Dirección predeterminada para pedidos');

            $table->timestamps();

            // Índices
            $table->index('user_id');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
```

**Campos de `addresses`:**
- `id` (bigInteger, PK)
- `user_id` (bigInteger, FK → users.id)
- `label` (string) - Ej: "Casa", "Trabajo", "Casa de mamá"
- `province` (string) - Ej: "San José", "Alajuela"
- `canton` (string) - Ej: "Central", "Escazú"
- `district` (string) - Ej: "Carmen", "San Rafael"
- `address_details` (text) - Ej: "200m norte de la iglesia, casa verde"
- `is_default` (boolean) - true/false
- `created_at` (timestamp)
- `updated_at` (timestamp)

---

### 5.3: Nueva Tabla `cr_locations`

**Migración:** `create_cr_locations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cr_locations', function (Blueprint $table) {
            $table->id();

            // IDs originales del JSON
            $table->integer('province_id');
            $table->integer('canton_id')->nullable();

            // Nombres
            $table->string('province_name', 100);
            $table->string('canton_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();

            // Tipo de registro (province, canton, district)
            $table->enum('type', ['province', 'canton', 'district']);

            $table->timestamps();

            // Índices
            $table->index('province_id');
            $table->index('canton_id');
            $table->index('type');
            $table->unique(['province_id', 'canton_id', 'district_name'], 'unique_location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cr_locations');
    }
};
```

**Campos de `cr_locations`:**
- `id` (bigInteger, PK)
- `province_id` (integer) - ID original del JSON (1-7)
- `canton_id` (integer, nullable) - ID original del JSON (101-706)
- `province_name` (string) - Ej: "San José", "Alajuela"
- `canton_name` (string, nullable) - Ej: "Central", "Escazú"
- `district_name` (string, nullable) - Ej: "Carmen", "San Rafael"
- `type` (enum) - 'province', 'canton', 'district'
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Estructura de datos:**
- 7 registros tipo 'province'
- ~130 registros tipo 'canton'
- ~500 registros tipo 'district'

---

## 📋 PLAN DE IMPLEMENTACIÓN DETALLADO

### Subtarea 5.1: Modificar Tabla Users

**Objetivo:** Agregar campo `phone` a la tabla users

**Archivos a crear/modificar:**

1. **Migración: `database/migrations/YYYY_MM_DD_add_phone_to_users_table.php`**

2. **Actualizar Modelo User: `app/Models/User.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone', // ⬅️ AGREGAR
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ========================================
    // MÉTODOS HELPER PARA ROLES
    // ========================================

    public function getRoleName(): string
    {
        return $this->roles->first()?->name ?? 'Sin rol';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function isCliente(): bool
    {
        return $this->hasRole('Cliente');
    }

    // ========================================
    // RELACIONES
    // ========================================

    /**
     * Relación con direcciones del usuario (1:N)
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Relación con pedidos del usuario (1:N)
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
```

**Comandos:**

```bash
# Crear migración
php artisan make:migration add_phone_to_users_table --table=users

# Ejecutar migración
php artisan migrate
```

**Verificación:**
```sql
-- En PostgreSQL
\d users;
-- Debe mostrar columna 'phone'
```

---

### Subtarea 5.2: Crear Tabla Addresses

**Objetivo:** Implementar sistema de direcciones guardadas

**Archivos a crear:**

1. **Migración: `database/migrations/YYYY_MM_DD_create_addresses_table.php`**

2. **Modelo Address: `app/Models/Address.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'province',
        'canton',
        'district',
        'address_details',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ========================================
    // RELACIONES
    // ========================================

    /**
     * Relación con usuario (N:1)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Solo direcciones del usuario especificado
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Dirección predeterminada
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ========================================
    // MÉTODOS HELPER
    // ========================================

    /**
     * Obtener dirección completa formateada
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->province}, {$this->canton}, {$this->district}. {$this->address_details}";
    }

    /**
     * Convertir a formato para order_shipping_addresses
     */
    public function toShippingSnapshot(): array
    {
        return [
            'province' => $this->province,
            'canton' => $this->canton,
            'district' => $this->district,
            'address_details' => $this->address_details,
        ];
    }
}
```

**Comandos:**

```bash
# Crear migración
php artisan make:migration create_addresses_table

# Crear modelo
php artisan make:model Address

# Ejecutar migración
php artisan migrate
```

---

### Subtarea 5.3: Validaciones (Form Requests)

**Objetivo:** Validaciones diferenciadas para Admin y Cliente

**Archivos a crear:**

1. **UpdateProfileRequest: `app/Http/Requests/UpdateProfileRequest.php`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El usuario solo puede actualizar su propio perfil
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        // Teléfono OBLIGATORIO para Clientes, opcional para Admin
        if ($user->isCliente()) {
            $rules['phone'] = ['required', 'string', 'max:20'];
        } else {
            $rules['phone'] = ['nullable', 'string', 'max:20'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'phone.required' => 'El teléfono es obligatorio para clientes.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
```

2. **StoreAddressRequest: `app/Http/Requests/StoreAddressRequest.php`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CrLocation;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo clientes pueden crear direcciones
        return $this->user()->isCliente();
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'province' => ['required', 'string', 'max:100'],
            'canton' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address_details' => ['required', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta es obligatoria.',
            'province.required' => 'La provincia es obligatoria.',
            'canton.required' => 'El cantón es obligatorio.',
            'district.required' => 'El distrito es obligatorio.',
            'address_details.required' => 'Los detalles de la dirección son obligatorios.',
        ];
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que la ubicación existe en cr_locations
            if (!CrLocation::locationExists(
                $this->province,
                $this->canton,
                $this->district
            )) {
                $validator->errors()->add(
                    'district',
                    'La combinación de provincia, cantón y distrito no es válida.'
                );
            }
        });
    }
}
```

3. **UpdateAddressRequest: `app/Http/Requests/UpdateAddressRequest.php`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CrLocation;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El usuario solo puede actualizar sus propias direcciones
        $address = $this->route('address');
        return $address && $address->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:50'],
            'province' => ['sometimes', 'string', 'max:100'],
            'canton' => ['sometimes', 'string', 'max:100'],
            'district' => ['sometimes', 'string', 'max:100'],
            'address_details' => ['sometimes', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Si se están actualizando los campos de ubicación, validar
            if ($this->has('province') || $this->has('canton') || $this->has('district')) {
                $address = $this->route('address');

                $province = $this->province ?? $address->province;
                $canton = $this->canton ?? $address->canton;
                $district = $this->district ?? $address->district;

                if (!CrLocation::locationExists($province, $canton, $district)) {
                    $validator->errors()->add(
                        'district',
                        'La combinación de provincia, cantón y distrito no es válida.'
                    );
                }
            }
        });
    }
}
```

**Comandos:**

```bash
php artisan make:request UpdateProfileRequest
php artisan make:request StoreAddressRequest
php artisan make:request UpdateAddressRequest
```

---

### Subtarea 5.4: Controladores

#### 5.4.1: ProfileController

**Archivo:** `app/Http/Controllers/Api/v1/ProfileController.php`

```php
<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Obtener perfil del usuario autenticado
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->getRoleName(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Actualizar perfil del usuario autenticado
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validated();

        // Actualizar campos básicos
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        // Actualizar contraseña solo si se proporciona
        if (isset($validated['password']) && !empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->getRoleName(),
                'updated_at' => $user->updated_at,
            ],
        ]);
    }
}
```

**Comando:**

```bash
php artisan make:controller Api/v1/ProfileController
```

---

#### 5.4.2: AddressController (Clientes)

**Archivo:** `app/Http/Controllers/Api/v1/AddressController.php`

```php
<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    /**
     * Listar todas las direcciones del usuario autenticado
     */
    public function index(): JsonResponse
    {
        $addresses = Address::forUser(auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    /**
     * Crear nueva dirección
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();
            $validated['user_id'] = auth()->id();

            // Si se marca como predeterminada, desmarcar las demás
            if ($validated['is_default'] ?? false) {
                Address::forUser(auth()->id())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Si es la primera dirección, marcarla como predeterminada
            $isFirstAddress = Address::forUser(auth()->id())->count() === 0;
            if ($isFirstAddress) {
                $validated['is_default'] = true;
            }

            $address = Address::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dirección creada exitosamente',
                'data' => $address,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener dirección específica
     */
    public function show(Address $address): JsonResponse
    {
        // Verificar que la dirección pertenece al usuario
        if ($address->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $address,
        ]);
    }

    /**
     * Actualizar dirección
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Si se marca como predeterminada, desmarcar las demás
            if (isset($validated['is_default']) && $validated['is_default']) {
                Address::forUser(auth()->id())
                    ->where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $address->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dirección actualizada exitosamente',
                'data' => $address->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar dirección
     */
    public function destroy(Address $address): JsonResponse
    {
        // Verificar que la dirección pertenece al usuario
        if ($address->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $wasDefault = $address->is_default;
            $address->delete();

            // Si era predeterminada, marcar otra como predeterminada
            if ($wasDefault) {
                $nextAddress = Address::forUser(auth()->id())->first();
                if ($nextAddress) {
                    $nextAddress->update(['is_default' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dirección eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar dirección como predeterminada
     */
    public function setDefault(Address $address): JsonResponse
    {
        // Verificar que la dirección pertenece al usuario
        if ($address->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Desmarcar todas las direcciones del usuario
            Address::forUser(auth()->id())
                ->update(['is_default' => false]);

            // Marcar esta como predeterminada
            $address->update(['is_default' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dirección predeterminada actualizada',
                'data' => $address->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar dirección predeterminada',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

**Comando:**

```bash
php artisan make:controller Api/v1/AddressController --resource
```

---

#### 5.4.3: AdminAddressController (Admin - Solo Lectura)

**Archivo:** `app/Http/Controllers/Api/v1/AdminAddressController.php`

```php
<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminAddressController extends Controller
{
    /**
     * Listar TODAS las direcciones de TODOS los usuarios
     * Con filtro opcional por user_id
     */
    public function index(): JsonResponse
    {
        $query = Address::with('user:id,name,email');

        // Filtrar por user_id si se proporciona
        if (request()->has('user_id')) {
            $query->forUser(request()->get('user_id'));
        }

        // Ordenar
        $addresses = $query
            ->orderBy('user_id', 'asc')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    /**
     * Ver dirección específica de cualquier usuario
     */
    public function show(Address $address): JsonResponse
    {
        $address->load('user:id,name,email,phone');

        return response()->json([
            'success' => true,
            'data' => $address,
        ]);
    }

    /**
     * Listar direcciones de un usuario específico
     */
    public function byUser(User $user): JsonResponse
    {
        $addresses = Address::forUser($user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'addresses' => $addresses,
            ],
        ]);
    }
}
```

**Comando:**

```bash
php artisan make:controller Api/v1/AdminAddressController
```

---

#### 5.4.4: LocationController

**Archivo:** `app/Http/Controllers/Api/v1/LocationController.php`

```php
<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\CrLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    /**
     * Obtener estructura jerárquica completa
     * GET /api/v1/locations
     *
     * Endpoint público (no requiere autenticación)
     * Cachea resultado por 24 horas
     */
    public function index(): JsonResponse
    {
        // Cachear por 24 horas (estos datos no cambian frecuentemente)
        $hierarchy = Cache::remember('cr_locations_hierarchy', 86400, function () {
            return CrLocation::getHierarchy();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'provincias' => $hierarchy
            ],
        ]);
    }
}
```

**Comando:**

```bash
php artisan make:controller Api/v1/LocationController
```

---

### Subtarea 5.5: Rutas API (Archivos Separados)

**Objetivo:** Crear archivos de rutas separados en `routes/v1/` y llamarlos desde `routes/api.php`

#### 5.5.1: Archivo de Rutas - Profile

**Archivo:** `routes/v1/profile.php`

```php
<?php

use App\Http\Controllers\Api\v1\ProfileController;
use Illuminate\Support\Facades\Route;

// RUTAS PROTEGIDAS - Todos los usuarios autenticados
Route::middleware(['auth:sanctum'])->group(function () {
    // Ver perfil propio
    Route::get('/profile', [ProfileController::class, 'show']);

    // Actualizar perfil propio
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile', [ProfileController::class, 'update']);
});
```

---

#### 5.5.2: Archivo de Rutas - Addresses (Clientes)

**Archivo:** `routes/v1/addresses.php`

```php
<?php

use App\Http\Controllers\Api\v1\AddressController;
use Illuminate\Support\Facades\Route;

// RUTAS PROTEGIDAS - Solo Clientes
Route::middleware(['auth:sanctum', 'role:Cliente'])->group(function () {
    // Listar direcciones del cliente autenticado
    Route::get('/addresses', [AddressController::class, 'index']);

    // Crear nueva dirección
    Route::post('/addresses', [AddressController::class, 'store']);

    // Ver dirección específica
    Route::get('/addresses/{address}', [AddressController::class, 'show']);

    // Actualizar dirección
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);

    // Eliminar dirección
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

    // Marcar dirección como predeterminada
    Route::patch('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);
});
```

---

#### 5.5.3: Archivo de Rutas - Admin Addresses

**Archivo:** `routes/v1/admin_addresses.php`

```php
<?php

use App\Http\Controllers\Api\v1\AdminAddressController;
use Illuminate\Support\Facades\Route;

// RUTAS PROTEGIDAS - Solo Super Admin (Solo Lectura)
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    // Listar TODAS las direcciones de TODOS los usuarios
    // Opcionalmente filtrar por user_id: ?user_id=5
    Route::get('/admin/addresses', [AdminAddressController::class, 'index']);

    // Ver dirección específica de cualquier usuario
    Route::get('/admin/addresses/{address}', [AdminAddressController::class, 'show']);

    // Listar direcciones de un usuario específico
    Route::get('/admin/users/{user}/addresses', [AdminAddressController::class, 'byUser']);
});
```

---

#### 5.5.4: Archivo de Rutas - Locations

**Archivo:** `routes/v1/locations.php`

```php
<?php

use App\Http\Controllers\Api\v1\LocationController;
use Illuminate\Support\Facades\Route;

// RUTA PÚBLICA - No requiere autenticación
// Obtener datos territoriales de Costa Rica
Route::get('/locations', [LocationController::class, 'index']);
```

---

#### 5.5.5: Actualizar routes/api.php

**Archivo:** `routes/api.php`

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestMailController;

// RUTA DE PRUEBA EMAIL
Route::post('/test-email', [TestMailController::class, 'sendTestEmail']);

// ========================================
// API VERSION 1
// ========================================
Route::prefix('v1')->group(function () {

    // MÓDULO DE AUTENTICACIÓN
    require base_path('routes/v1/auth.php');

    // MÓDULO DE CATEGORÍAS
    require base_path('routes/v1/categories.php');

    // MÓDULO DE PRODUCTOS
    require base_path('routes/v1/products.php');

    // MÓDULO DE PEDIDOS - CLIENTES
    require base_path('routes/v1/orders.php');

    // MÓDULO DE PEDIDOS - ADMIN
    require base_path('routes/v1/admin_orders.php');

    // ========================================
    // FASE 5: PERFILES, DIRECCIONES Y UBICACIONES
    // ========================================

    // MÓDULO DE PERFIL (Todos los usuarios)
    require base_path('routes/v1/profile.php');

    // MÓDULO DE DIRECCIONES - CLIENTES
    require base_path('routes/v1/addresses.php');

    // MÓDULO DE DIRECCIONES - ADMIN (Solo lectura)
    require base_path('routes/v1/admin_addresses.php');

    // MÓDULO DE UBICACIONES DE COSTA RICA (Público)
    require base_path('routes/v1/locations.php');

    // Ruta de ejemplo para verificar si el usuario está autenticado
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });

});
```

**Verificación:**

```bash
php artisan route:list --path=api/v1
```

---

### Subtarea 5.6: Sistema de Ubicaciones de Costa Rica

**Objetivo:** Cargar datos territoriales de Costa Rica en base de datos

#### 5.6.1: Crear Migración

```bash
php artisan make:migration create_cr_locations_table
```

#### 5.6.2: Crear Modelo CrLocation

**Archivo:** `app/Models/CrLocation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CrLocation extends Model
{
    protected $fillable = [
        'province_id',
        'canton_id',
        'province_name',
        'canton_name',
        'district_name',
        'type',
    ];

    /**
     * Obtener estructura jerárquica completa
     */
    public static function getHierarchy(): array
    {
        $locations = self::all();

        $provinces = [];

        foreach ($locations->where('type', 'province') as $province) {
            $provinceData = [
                'id' => $province->province_id,
                'nombre' => $province->province_name,
                'cantones' => []
            ];

            // Obtener cantones de esta provincia
            $cantones = $locations->where('type', 'canton')
                ->where('province_id', $province->province_id);

            foreach ($cantones as $canton) {
                $cantonData = [
                    'id' => $canton->canton_id,
                    'nombre' => $canton->canton_name,
                    'distritos' => []
                ];

                // Obtener distritos de este cantón
                $distritos = $locations->where('type', 'district')
                    ->where('province_id', $province->province_id)
                    ->where('canton_id', $canton->canton_id)
                    ->pluck('district_name')
                    ->toArray();

                $cantonData['distritos'] = $distritos;
                $provinceData['cantones'][] = $cantonData;
            }

            $provinces[] = $provinceData;
        }

        return $provinces;
    }

    /**
     * Validar que existe una ubicación
     */
    public static function locationExists(string $province, string $canton, string $district): bool
    {
        return self::where('type', 'district')
            ->where('province_name', $province)
            ->where('canton_name', $canton)
            ->where('district_name', $district)
            ->exists();
    }
}
```

**Comando:**

```bash
php artisan make:model CrLocation
```

#### 5.6.3: Crear Seeder

**Archivo:** `database/seeders/CrLocationSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CrLocation;

class CrLocationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Cargando ubicaciones de Costa Rica...');

        // Leer JSON
        $json = file_get_contents(base_path('docs/cr_shipping_data.json'));
        $data = json_decode($json, true);

        $totalProvincias = 0;
        $totalCantones = 0;
        $totalDistritos = 0;

        foreach ($data['provincias'] as $provincia) {
            // Insertar provincia
            CrLocation::create([
                'province_id' => $provincia['id'],
                'canton_id' => null,
                'province_name' => $provincia['nombre'],
                'canton_name' => null,
                'district_name' => null,
                'type' => 'province',
            ]);
            $totalProvincias++;

            foreach ($provincia['cantones'] as $canton) {
                // Insertar cantón
                CrLocation::create([
                    'province_id' => $provincia['id'],
                    'canton_id' => $canton['id'],
                    'province_name' => $provincia['nombre'],
                    'canton_name' => $canton['nombre'],
                    'district_name' => null,
                    'type' => 'canton',
                ]);
                $totalCantones++;

                foreach ($canton['distritos'] as $distrito) {
                    // Insertar distrito
                    CrLocation::create([
                        'province_id' => $provincia['id'],
                        'canton_id' => $canton['id'],
                        'province_name' => $provincia['nombre'],
                        'canton_name' => $canton['nombre'],
                        'district_name' => $distrito,
                        'type' => 'district',
                    ]);
                    $totalDistritos++;
                }
            }
        }

        $this->command->info("✅ {$totalProvincias} provincias cargadas");
        $this->command->info("✅ {$totalCantones} cantones cargados");
        $this->command->info("✅ {$totalDistritos} distritos cargados");
        $this->command->info('✅ Ubicaciones de Costa Rica cargadas exitosamente');
    }
}
```

**Comando:**

```bash
php artisan make:seeder CrLocationSeeder
```

#### 5.6.4: Ejecutar Migración y Seeder

```bash
# Ejecutar migración
php artisan migrate

# Ejecutar seeder
php artisan db:seed --class=CrLocationSeeder
```

---

## 📍 ENDPOINTS DE API

### Perfil (Todos los Usuarios)

#### 1. Ver Perfil

**Endpoint:** `GET /api/v1/profile`
**Auth:** Requerido (Sanctum)
**Roles:** Todos (Admin, Cliente)

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "María González",
    "email": "cliente1@test.com",
    "phone": "+506 8888 8888",
    "role": "Cliente",
    "created_at": "2025-01-10T10:00:00.000000Z",
    "updated_at": "2025-01-14T15:30:00.000000Z"
  }
}
```

---

#### 2. Actualizar Perfil

**Endpoint:** `PUT|PATCH /api/v1/profile`
**Auth:** Requerido (Sanctum)
**Roles:** Todos (Admin, Cliente)

**Request Body (Cliente):**
```json
{
  "name": "María González Pérez",
  "email": "maria.nueva@test.com",
  "phone": "+506 8888 9999",
  "password": "nuevaPassword123",
  "password_confirmation": "nuevaPassword123"
}
```

**Notas:**
- `phone` es OBLIGATORIO para Clientes
- `phone` es OPCIONAL para Super Admin
- `password` es opcional (solo si se quiere cambiar)
- `email` debe ser único

**Response 200:**
```json
{
  "success": true,
  "message": "Perfil actualizado exitosamente",
  "data": {
    "id": 1,
    "name": "María González Pérez",
    "email": "maria.nueva@test.com",
    "phone": "+506 8888 9999",
    "role": "Cliente",
    "updated_at": "2025-01-14T16:00:00.000000Z"
  }
}
```

---

### Direcciones - Clientes

#### 3. Listar Direcciones Propias

**Endpoint:** `GET /api/v1/addresses`
**Auth:** Requerido (Sanctum)
**Roles:** Cliente

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "label": "Casa",
      "province": "San José",
      "canton": "Central",
      "district": "Carmen",
      "address_details": "200m norte de la iglesia, casa verde",
      "is_default": true,
      "created_at": "2025-01-10T10:00:00.000000Z",
      "updated_at": "2025-01-14T15:30:00.000000Z"
    }
  ]
}
```

---

#### 4. Crear Dirección

**Endpoint:** `POST /api/v1/addresses`
**Auth:** Requerido (Sanctum)
**Roles:** Cliente

**Request Body:**
```json
{
  "label": "Casa",
  "province": "San José",
  "canton": "Central",
  "district": "Carmen",
  "address_details": "200m norte de la iglesia, casa verde",
  "is_default": false
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Dirección creada exitosamente",
  "data": {
    "id": 1,
    "user_id": 2,
    "label": "Casa",
    "province": "San José",
    "canton": "Central",
    "district": "Carmen",
    "address_details": "200m norte de la iglesia, casa verde",
    "is_default": true,
    "created_at": "2025-01-14T16:00:00.000000Z",
    "updated_at": "2025-01-14T16:00:00.000000Z"
  }
}
```

---

### Direcciones - Admin (Solo Lectura)

#### 5. Ver Todas las Direcciones

**Endpoint:** `GET /api/v1/admin/addresses`
**Auth:** Requerido (Sanctum)
**Roles:** Super Admin

**Query Params (opcionales):**
- `user_id` - Filtrar por usuario específico

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "label": "Casa",
      "province": "San José",
      "canton": "Central",
      "district": "Carmen",
      "address_details": "200m norte de la iglesia, casa verde",
      "is_default": true,
      "created_at": "2025-01-10T10:00:00.000000Z",
      "updated_at": "2025-01-14T15:30:00.000000Z",
      "user": {
        "id": 2,
        "name": "María González",
        "email": "cliente1@test.com"
      }
    }
  ]
}
```

---

#### 6. Ver Direcciones de Usuario Específico

**Endpoint:** `GET /api/v1/admin/users/{user_id}/addresses`
**Auth:** Requerido (Sanctum)
**Roles:** Super Admin

**Response 200:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "María González",
      "email": "cliente1@test.com",
      "phone": "+506 8888 8888"
    },
    "addresses": [
      {
        "id": 1,
        "user_id": 2,
        "label": "Casa",
        "province": "San José",
        "canton": "Central",
        "district": "Carmen",
        "address_details": "200m norte de la iglesia, casa verde",
        "is_default": true,
        "created_at": "2025-01-10T10:00:00.000000Z",
        "updated_at": "2025-01-14T15:30:00.000000Z"
      }
    ]
  }
}
```

---

### Ubicaciones de Costa Rica (Público)

#### 7. Obtener Datos Territoriales

**Endpoint:** `GET /api/v1/locations`
**Auth:** No requerido (público)
**Cacheo:** 24 horas

**Response 200:**
```json
{
  "success": true,
  "data": {
    "provincias": [
      {
        "id": 1,
        "nombre": "San José",
        "cantones": [
          {
            "id": 101,
            "nombre": "San José",
            "distritos": ["Carmen", "Merced", "Hospital", "Catedral"]
          },
          {
            "id": 102,
            "nombre": "Escazú",
            "distritos": ["Escazú", "San Antonio", "San Rafael"]
          }
        ]
      },
      {
        "id": 2,
        "nombre": "Alajuela",
        "cantones": [
          {
            "id": 201,
            "nombre": "Alajuela",
            "distritos": ["Alajuela", "San José", "Carrizal"]
          }
        ]
      }
    ]
  }
}
```

**Uso en Frontend:**
```typescript
// Cachear en estado global al iniciar app
const { data } = await fetch('/api/v1/locations').then(r => r.json());
// Usar para llenar dropdowns en cascada
```

---

## 🔐 SEGURIDAD Y VALIDACIONES

### Reglas de Autorización

| Recurso | Acción | Admin | Cliente |
|---------|--------|-------|---------|
| **Perfil** | Ver propio | ✅ | ✅ |
| **Perfil** | Editar propio | ✅ | ✅ |
| **Perfil** | Ver de otros | ❌ | ❌ |
| **Direcciones** | Ver todas (todos los usuarios) | ✅ (solo lectura) | ❌ |
| **Direcciones** | CRUD propias | ❌ | ✅ |
| **Direcciones** | Ver de otros | ✅ (solo lectura) | ❌ |
| **Direcciones** | Editar de otros | ❌ | ❌ |
| **Ubicaciones** | Ver datos CR | ✅ (público) | ✅ (público) |

### Permisos de Spatie

**Super Admin:**
- `view own profile` ✅
- `edit own profile` ✅
- `view all addresses` ✅ (Ver todas las direcciones, solo lectura)

**Cliente:**
- `view own profile` ✅
- `edit own profile` ✅
- `manage own addresses` ✅ (CRUD de sus propias direcciones)

### Validaciones Especiales

1. **Teléfono según rol:**
   - Cliente: OBLIGATORIO
   - Admin: OPCIONAL

2. **Email único:**
   - Debe ser único en la tabla users
   - Excepto el email del propio usuario al editar

3. **Contraseña:**
   - Solo se actualiza si se proporciona
   - Mínimo 8 caracteres
   - Requiere confirmación

4. **Dirección predeterminada:**
   - Solo una por usuario
   - Primera dirección es automáticamente predeterminada
   - Al marcar una, se desmarca la anterior
   - Al eliminar la predeterminada, se marca otra automáticamente

5. **Validación de ubicación real:**
   - Al crear/actualizar dirección, se valida que la combinación provincia+cantón+distrito exista en `cr_locations`
   - Previene errores de escritura y datos inconsistentes

6. **Admin no puede crear/editar direcciones:**
   - Solo puede VERLAS (lectura)
   - Útil para soporte al cliente

---

## 🔗 INTEGRACIÓN CON PEDIDOS

### Flujo de Creación de Pedido Online con Envío

1. **Cliente autenticado accede al checkout:**
   - Frontend pre-llena: `name`, `email`, `phone` desde `auth()->user()`
   - Frontend carga direcciones guardadas desde `GET /api/v1/addresses`
   - Frontend carga datos de CR desde `GET /api/v1/locations` (cacheados)
   - Cliente selecciona provincia → cantón → distrito (dropdowns en cascada)
   - Cliente selecciona dirección guardada o crea nueva

2. **Cliente crea pedido:**
   - Envía a `POST /api/v1/orders` con `address_id` (si usa guardada)
   - O envía dirección nueva inline

3. **Backend (OrderService):**
   - Si recibe `address_id`, busca en `addresses` del usuario
   - Copia datos a `order_shipping_addresses` (snapshot inmutable)
   - Guarda pedido con dirección de envío

**Código de ejemplo en OrderService:**

```php
public function createOnlineOrder(array $data): Order
{
    DB::beginTransaction();

    try {
        // ... validaciones ...

        // Crear pedido
        $order = Order::create([
            'user_id' => auth()->id(),
            'customer_name' => auth()->user()->name,
            'customer_email' => auth()->user()->email,
            'customer_phone' => auth()->user()->phone,
            // ... otros campos ...
        ]);

        // Si es envío, crear snapshot de dirección
        if ($data['delivery_type'] === 'delivery') {

            // Opción 1: Dirección guardada
            if (isset($data['address_id'])) {
                $address = Address::findOrFail($data['address_id']);

                // Verificar que pertenece al usuario
                if ($address->user_id !== auth()->id()) {
                    throw new \Exception('Dirección no pertenece al usuario');
                }

                OrderShippingAddress::create([
                    'order_id' => $order->id,
                    ...$address->toShippingSnapshot(),
                ]);
            }

            // Opción 2: Dirección nueva (inline)
            else {
                // Validar que la ubicación existe
                if (!CrLocation::locationExists(
                    $data['province'],
                    $data['canton'],
                    $data['district']
                )) {
                    throw new \Exception('Ubicación no válida');
                }

                OrderShippingAddress::create([
                    'order_id' => $order->id,
                    'province' => $data['province'],
                    'canton' => $data['canton'],
                    'district' => $data['district'],
                    'address_details' => $data['address_details'],
                ]);
            }
        }

        // ... items, stock, etc ...

        DB::commit();
        return $order;

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

---

## 🧪 TESTING Y VALIDACIÓN

### Checklist de Testing

#### Perfil

- [ ] **Test 1:** Admin puede ver su propio perfil
- [ ] **Test 2:** Cliente puede ver su propio perfil
- [ ] **Test 3:** Admin puede actualizar su perfil sin teléfono (opcional)
- [ ] **Test 4:** Cliente puede actualizar su perfil con teléfono (obligatorio)
- [ ] **Test 5:** Cliente NO puede actualizar perfil sin teléfono (422)
- [ ] **Test 6:** Usuario puede cambiar contraseña
- [ ] **Test 7:** Usuario NO puede usar email duplicado (422)
- [ ] **Test 8:** Contraseña requiere confirmación
- [ ] **Test 9:** Usuario no autenticado no puede acceder (401)

#### Direcciones - Clientes

- [ ] **Test 10:** Cliente puede listar sus direcciones
- [ ] **Test 11:** Cliente puede crear dirección
- [ ] **Test 12:** Primera dirección se marca como predeterminada automáticamente
- [ ] **Test 13:** Cliente puede ver dirección específica
- [ ] **Test 14:** Cliente NO puede ver dirección de otro usuario (403)
- [ ] **Test 15:** Cliente puede actualizar su dirección
- [ ] **Test 16:** Cliente puede eliminar su dirección
- [ ] **Test 17:** Al eliminar predeterminada, se marca otra
- [ ] **Test 18:** Cliente puede marcar dirección como predeterminada
- [ ] **Test 19:** Solo una dirección es predeterminada a la vez
- [ ] **Test 20:** Admin NO puede acceder a endpoints de clientes (403)
- [ ] **Test 21:** Validación rechaza provincia vacía (422)
- [ ] **Test 22:** Validación rechaza cantón vacío (422)
- [ ] **Test 23:** Validación rechaza distrito vacío (422)
- [ ] **Test 24:** Validación rechaza ubicación no válida (422)

#### Direcciones - Admin

- [ ] **Test 25:** Admin puede ver todas las direcciones
- [ ] **Test 26:** Admin puede filtrar por user_id
- [ ] **Test 27:** Admin puede ver dirección específica de cualquier usuario
- [ ] **Test 28:** Admin puede ver direcciones de usuario específico
- [ ] **Test 29:** Cliente NO puede acceder a endpoints admin (403)

#### Ubicaciones de Costa Rica

- [ ] **Test 30:** Endpoint público devuelve estructura jerárquica completa
- [ ] **Test 31:** Contiene 7 provincias
- [ ] **Test 32:** No requiere autenticación
- [ ] **Test 33:** Respuesta está cacheada (segunda llamada más rápida)

#### Integración con Pedidos

- [ ] **Test 34:** Pedido usa dirección guardada correctamente
- [ ] **Test 35:** Snapshot en order_shipping_addresses es inmutable
- [ ] **Test 36:** Cambio en address NO afecta pedidos antiguos
- [ ] **Test 37:** Pre-llenado de datos de usuario en checkout
- [ ] **Test 38:** Validación de ubicación en pedidos inline

---

## 📝 NOTAS IMPORTANTES

### Diferencias Clave entre Tablas

| Característica | `addresses` | `order_shipping_addresses` | `cr_locations` |
|----------------|-------------|----------------------------|----------------|
| **Propósito** | Libreta de direcciones del usuario | Snapshot de dirección del pedido | Datos territoriales de CR |
| **Relación** | `users` (1:N) | `orders` (1:1) | Ninguna (datos maestros) |
| **Editable** | ✅ SÍ | ❌ NO (inmutable) | ❌ NO (datos oficiales) |
| **Predeterminada** | ✅ Tiene `is_default` | ❌ No aplica | ❌ No aplica |
| **Etiqueta** | ✅ `label` (Casa, Trabajo) | ❌ No tiene | ❌ No tiene |
| **Uso** | Direcciones guardadas para reutilizar | Registro histórico de ESTE pedido | Validación y dropdowns |

### Separación de Responsabilidades

**Clientes (AddressController):**
- CRUD completo de SUS PROPIAS direcciones
- No pueden ver direcciones de otros usuarios

**Admin (AdminAddressController):**
- Solo LECTURA de todas las direcciones
- No pueden crear/editar/eliminar
- Útil para soporte al cliente

**Ubicaciones (LocationController):**
- Endpoint público (no requiere auth)
- Solo lectura
- Cacheado por 24 horas

### Flujo Completo

```
1. Sistema carga datos de CR → cr_locations (seeder)
2. Usuario crea cuenta → Rol: Cliente
3. Usuario actualiza perfil → Agrega teléfono (obligatorio)
4. Usuario guarda dirección → Validada contra cr_locations
5. Primera dirección → Marcada predeterminada
6. Usuario crea pedido → Selecciona dirección guardada
7. Sistema copia dirección → Snapshot inmutable en order_shipping_addresses
8. Usuario edita dirección guardada → Pedidos antiguos NO cambian
9. Admin puede ver direcciones → Solo lectura, para soporte
```

### Consideraciones de Rendimiento

1. **Índices creados:**
   - `users.phone` (para búsquedas)
   - `addresses.user_id` (para relaciones)
   - `addresses.is_default` (para filtros)
   - `cr_locations.province_id` (para jerarquía)
   - `cr_locations.canton_id` (para jerarquía)
   - `cr_locations.type` (para filtros)

2. **Cacheo:**
   - Endpoint `/api/v1/locations` cacheado por 24 horas
   - Reduce consultas a base de datos
   - Frontend puede cachear en memoria también

3. **Eager Loading recomendado:**
   ```php
   // Al cargar usuario con direcciones
   $user = User::with('addresses')->find($id);

   // Al cargar pedido con dirección de envío
   $order = Order::with('shippingAddress')->find($id);

   // Al listar direcciones (admin)
   $addresses = Address::with('user:id,name,email')->get();
   ```

---

## 🚀 ORDEN DE EJECUCIÓN RECOMENDADO

### Día 1: Modificación de Users y Ubicaciones

1. ✅ Subtarea 5.1: Agregar campo `phone` a tabla `users`
   - Crear migración
   - Actualizar modelo User
   - Ejecutar migración
   - Verificar en base de datos

2. ✅ Subtarea 5.6: Sistema de ubicaciones de Costa Rica
   - Crear migración cr_locations
   - Crear modelo CrLocation
   - Crear seeder CrLocationSeeder
   - Ejecutar migración y seeder
   - Verificar datos cargados

### Día 2: Sistema de Direcciones

1. ✅ Subtarea 5.2: Crear tabla `addresses`
   - Crear migración
   - Crear modelo Address
   - Ejecutar migración
   - Probar relaciones

### Día 3: Validaciones

1. ✅ Subtarea 5.3: Crear Form Requests
   - UpdateProfileRequest
   - StoreAddressRequest (con validación de ubicación)
   - UpdateAddressRequest (con validación de ubicación)

### Día 4: Controladores

1. ✅ Subtarea 5.4: Crear controladores
   - ProfileController
   - AddressController (Clientes)
   - AdminAddressController (Admin)
   - LocationController

### Día 5: Rutas y Testing

1. ✅ Subtarea 5.5: Crear archivos de rutas
   - routes/v1/profile.php
   - routes/v1/addresses.php
   - routes/v1/admin_addresses.php
   - routes/v1/locations.php
   - Actualizar routes/api.php
2. ✅ Testing completo con Postman
3. ✅ Integración con pedidos
4. ✅ Documentación final

---

## ⚠️ PUNTOS CRÍTICOS

1. **Teléfono obligatorio para clientes:**
   - Validar según rol en UpdateProfileRequest
   - Frontend debe mostrar campo requerido solo para clientes

2. **Solo una dirección predeterminada:**
   - Al marcar una, desmarcar todas las demás
   - Primera dirección automáticamente predeterminada
   - Al eliminar predeterminada, marcar otra

3. **Autorización estricta:**
   - Clientes solo pueden ver/editar SUS PROPIAS direcciones
   - Admin solo puede VER direcciones (no crear/editar/eliminar)
   - Middleware `role:Cliente` en rutas de clientes
   - Middleware `role:Super Admin` en rutas de admin

4. **Contraseña opcional al editar:**
   - Solo actualizar si se proporciona
   - Requiere confirmación
   - Nunca retornar contraseña en respuestas

5. **Snapshot inmutable:**
   - `order_shipping_addresses` NUNCA se edita
   - Cambios en `addresses` NO afectan pedidos antiguos

6. **Rutas separadas por módulo:**
   - Cada módulo en su propio archivo en `routes/v1/`
   - Llamados desde `routes/api.php` con `require`

7. **Validación de ubicaciones reales:**
   - Todas las direcciones deben validarse contra `cr_locations`
   - Previene inconsistencias y errores de escritura

8. **Cacheo de ubicaciones:**
   - Endpoint `/api/v1/locations` debe estar cacheado
   - Reduce carga en base de datos

---

## 🎯 CRITERIOS DE ÉXITO

La Fase 5 estará completada exitosamente cuando:

✅ Campo `phone` agregado a tabla `users`
✅ Tabla `addresses` creada con todos los campos
✅ Tabla `cr_locations` creada y cargada con datos
✅ Modelo User actualizado con relación `addresses`
✅ Modelo Address creado con relaciones y scopes
✅ Modelo CrLocation creado con métodos de validación
✅ Seeder CrLocationSeeder cargando 7 provincias, ~130 cantones, ~500 distritos
✅ Form Requests creados con validaciones correctas
✅ Validación de ubicaciones reales funcionando
✅ ProfileController funcionando correctamente
✅ AddressController (clientes) con CRUD completo
✅ AdminAddressController (admin) con lectura
✅ LocationController con endpoint público
✅ Archivos de rutas creados en `routes/v1/`
✅ routes/api.php actualizado
✅ Validación condicional de phone según rol
✅ Sistema de dirección predeterminada operativo
✅ Endpoint de ubicaciones cacheado
✅ Integración con pedidos probada
✅ Todos los tests pasando
✅ Documentación completa

---

## 📞 SIGUIENTE PASO

**¿Estás listo para comenzar con la Subtarea 5.1?**

El siguiente paso sería:

1. Crear la migración para agregar `phone` a `users`
2. Actualizar el modelo User
3. Ejecutar la migración
4. Verificar en PostgreSQL

**Comando inicial:**
```bash
cd Backend-Api-TAB-v3
php artisan make:migration add_phone_to_users_table --table=users
```

---

**Fecha de creación:** 2025-11-15
**Versión:** 1.2 - Plan Completo con Ubicaciones de Costa Rica
**Autor:** Plan de Implementación TAB-Backend-v3
**Proyecto:** Toys and Bricks E-commerce
