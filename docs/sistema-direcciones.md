# Sistema de Direcciones - Documentación

## Índice
- [Introducción](#introducción)
- [Estructura de Datos](#estructura-de-datos)
- [Endpoints Disponibles](#endpoints-disponibles)
- [Flujos de Uso](#flujos-de-uso)
- [Seguridad y Autenticación](#seguridad-y-autenticación)
- [Validaciones](#validaciones)
- [Ejemplos de Implementación](#ejemplos-de-implementación)

---

## Introducción

El sistema de direcciones permite a los usuarios gestionar sus direcciones de envío de dos formas:

1. **Direcciones guardadas**: El usuario guarda direcciones en su perfil para reutilizarlas
2. **Direcciones temporales**: El usuario ingresa una dirección solo para un pedido específico

Ambos flujos utilizan la misma fuente de datos geográficos de Costa Rica (`cr_locations`).

---

## Estructura de Datos

### Tabla `addresses` (Direcciones Guardadas)

Almacena direcciones permanentes asociadas a usuarios.

```sql
- id (PK)
- user_id (FK → users.id, cascade delete)
- label (string): "Casa", "Trabajo", "Otro"
- province (string): Nombre de la provincia
- canton (string): Nombre del cantón
- district (string): Nombre del distrito
- address_details (text): Señas exactas
- is_default (boolean): Dirección predeterminada
- timestamps
```

**Características:**
- Un usuario puede tener múltiples direcciones
- Solo UNA dirección puede ser predeterminada por usuario
- Se eliminan automáticamente si se elimina el usuario (cascade)

**Archivo:** `database/migrations/2025_11_15_153010_create_addresses_table.php`

---

### Tabla `cr_locations` (Catálogo Geográfico)

Contiene la estructura jerárquica de ubicaciones de Costa Rica.

```sql
- id (PK, autoincremental)
- province_id (int): ID original del JSON
- canton_id (int, nullable): ID original del JSON
- province_name (string): Nombre de la provincia
- canton_name (string, nullable): Nombre del cantón
- district_name (string, nullable): Nombre del distrito
- type (enum): 'province', 'canton', 'district'
- timestamps
```

**Jerarquía:**
```
Provincia (province_id, type='province')
  ├── Cantón (province_id, canton_id, type='canton')
  │     └── Distrito (province_id, canton_id, type='district')
```

**Archivo:** `database/migrations/2025_11_15_153706_create_cr_locations_table.php`

---

### Tabla `order_shipping_addresses` (Snapshot de Dirección en Pedido)

Almacena una copia inmutable de la dirección de envío al momento de crear el pedido.

```sql
- id (PK)
- order_id (FK → orders.id)
- province (string)
- canton (string)
- district (string)
- address_details (text)
- timestamps
```

**Importante:** Esta tabla es un **snapshot histórico**. No se actualiza si el usuario modifica su dirección guardada posteriormente.

**Archivo:** `database/migrations/2025_11_11_031608_create_order_shipping_addresses_table.php`

---

## Endpoints Disponibles

### 1. Obtener Ubicaciones de Costa Rica (Público)

**Endpoint:** `GET /api/v1/locations/cr`

**Autenticación:** No requerida (público)

**Descripción:** Retorna la estructura jerárquica completa de provincias, cantones y distritos de Costa Rica.

**Respuesta:**
```json
{
  "provincias": [
    {
      "id": 1,
      "nombre": "San José",
      "cantones": [
        {
          "id": 15,
          "nombre": "San José",
          "distritos": [
            {
              "id": 245,
              "nombre": "Carmen"
            },
            {
              "id": 246,
              "nombre": "Merced"
            }
          ]
        },
        {
          "id": 16,
          "nombre": "Escazú",
          "distritos": [
            {
              "id": 260,
              "nombre": "Escazú"
            },
            {
              "id": 261,
              "nombre": "San Antonio"
            }
          ]
        }
      ]
    }
  ],
  "total_provincias": 7
}
```

**Características:**
- Caché de 24 horas para optimizar rendimiento
- Los IDs retornados son los `id` autoincremental de la tabla `cr_locations`
- Usar estos IDs al crear pedidos con direcciones temporales

**Archivos relacionados:**
- Ruta: `routes/v1/locations.php`
- Controlador: `app/Http/Controllers/Api/v1/LocationController.php`
- Modelo: `app/Models/CrLocation.php`

---

### 2. Listar Direcciones del Usuario

**Endpoint:** `GET /api/v1/addresses`

**Autenticación:** Requerida (`auth:sanctum`, rol: `Cliente`)

**Descripción:** Retorna todas las direcciones guardadas del usuario autenticado.

**Respuesta:**
```json
{
  "addresses": [
    {
      "id": 5,
      "user_id": 12,
      "label": "Casa",
      "province": "San José",
      "canton": "Escazú",
      "district": "San Rafael",
      "address_details": "100m norte de la iglesia, casa blanca",
      "is_default": true,
      "created_at": "2025-11-15T10:30:00.000000Z",
      "updated_at": "2025-11-15T10:30:00.000000Z"
    },
    {
      "id": 8,
      "user_id": 12,
      "label": "Trabajo",
      "province": "Heredia",
      "canton": "Heredia",
      "district": "Heredia",
      "address_details": "Edificio Torre Médica, piso 3",
      "is_default": false,
      "created_at": "2025-11-16T14:20:00.000000Z",
      "updated_at": "2025-11-16T14:20:00.000000Z"
    }
  ]
}
```

**Ordenamiento:**
1. Por `is_default` descendente (predeterminadas primero)
2. Por `created_at` descendente (más recientes primero)

---

### 3. Crear Dirección

**Endpoint:** `POST /api/v1/addresses`

**Autenticación:** Requerida (`auth:sanctum`, rol: `Cliente`)

**Body:**
```json
{
  "label": "Casa",
  "province": "San José",
  "canton": "Escazú",
  "district": "San Rafael",
  "address_details": "100m norte de la iglesia, casa blanca",
  "is_default": false
}
```

**Validaciones:**
- `label`: requerido, solo acepta: "Casa", "Trabajo", "Otro"
- `province`, `canton`, `district`: requeridos, máximo 100 caracteres
- `address_details`: requerido, máximo 500 caracteres
- `is_default`: opcional (boolean)
- **Validación especial:** Verifica que la combinación provincia-cantón-distrito exista en `cr_locations`

**Lógica Especial:**
1. Si es la **primera dirección** del usuario → se marca automáticamente como predeterminada
2. Si se envía `is_default: true` → desmarca todas las demás direcciones del usuario

**Respuesta exitosa (201):**
```json
{
  "message": "Dirección creada exitosamente",
  "address": {
    "id": 10,
    "user_id": 12,
    "label": "Casa",
    "province": "San José",
    "canton": "Escazú",
    "district": "San Rafael",
    "address_details": "100m norte de la iglesia",
    "is_default": true,
    "created_at": "2025-11-20T15:45:00.000000Z",
    "updated_at": "2025-11-20T15:45:00.000000Z"
  }
}
```

**Archivos relacionados:**
- Request: `app/Http/Requests/v1/StoreAddressRequest.php`
- Controlador: `app/Http/Controllers/Api/v1/AddressController.php:32-55`

---

### 4. Ver Dirección Específica

**Endpoint:** `GET /api/v1/addresses/{id}`

**Autenticación:** Requerida (`auth:sanctum`, rol: `Cliente`)

**Descripción:** Obtiene una dirección específica. Solo puede ver sus propias direcciones.

**Respuesta:**
```json
{
  "address": {
    "id": 5,
    "user_id": 12,
    "label": "Casa",
    "province": "San José",
    "canton": "Escazú",
    "district": "San Rafael",
    "address_details": "100m norte de la iglesia",
    "is_default": true,
    "created_at": "2025-11-15T10:30:00.000000Z",
    "updated_at": "2025-11-15T10:30:00.000000Z"
  }
}
```

---

### 5. Actualizar Dirección

**Endpoint:** `PUT /api/v1/addresses/{id}`

**Autenticación:** Requerida (`auth:sanctum`, rol: `Cliente`)

**Body (todos los campos opcionales):**
```json
{
  "label": "Trabajo",
  "province": "Heredia",
  "canton": "Heredia",
  "district": "Mercedes",
  "address_details": "Edificio Central, oficina 301",
  "is_default": true
}
```

**Validaciones:**
- Todos los campos son opcionales (`sometimes`)
- Si se cambian campos de ubicación, valida la nueva combinación
- Si se marca como predeterminada, desmarca las demás

**Lógica Especial:**
- Solo actualiza los campos enviados (actualización parcial)
- Si cambias `province`, debes enviar también `canton` y `district` válidos para esa provincia
- Si marcas `is_default: true`, automáticamente desmarca las demás direcciones

**Respuesta exitosa (200):**
```json
{
  "message": "Dirección actualizada exitosamente",
  "address": {
    "id": 5,
    "user_id": 12,
    "label": "Trabajo",
    "province": "Heredia",
    "canton": "Heredia",
    "district": "Mercedes",
    "address_details": "Edificio Central, oficina 301",
    "is_default": true,
    "created_at": "2025-11-15T10:30:00.000000Z",
    "updated_at": "2025-11-20T16:00:00.000000Z"
  }
}
```

**Archivos relacionados:**
- Request: `app/Http/Requests/v1/UpdateAddressRequest.php`
- Controlador: `app/Http/Controllers/Api/v1/AddressController.php:74-93`

---

### 6. Eliminar Dirección

**Endpoint:** `DELETE /api/v1/addresses/{id}`

**Autenticación:** Requerida (`auth:sanctum`, rol: `Cliente`)

**Descripción:** Elimina una dirección del usuario.

**Lógica Especial:**
Si eliminas la dirección predeterminada, automáticamente asigna la primera dirección restante como nueva predeterminada.

**Respuesta exitosa (200):**
```json
{
  "message": "Dirección eliminada exitosamente"
}
```

**Archivos relacionados:**
- Controlador: `app/Http/Controllers/Api/v1/AddressController.php:99-117`

---

### 7. Marcar Dirección como Predeterminada

**Endpoint:** `POST /api/v1/addresses/{id}/set-default`

**Autenticación:** Requerida (`auth:sanctum`, rol: `Cliente`)

**Descripción:** Marca una dirección como predeterminada y desmarca las demás.

**Respuesta exitosa (200):**
```json
{
  "message": "Dirección marcada como predeterminada",
  "address": {
    "id": 8,
    "user_id": 12,
    "label": "Trabajo",
    "province": "Heredia",
    "canton": "Heredia",
    "district": "Heredia",
    "address_details": "Edificio Torre Médica",
    "is_default": true,
    "created_at": "2025-11-16T14:20:00.000000Z",
    "updated_at": "2025-11-20T16:30:00.000000Z"
  }
}
```

**Archivos relacionados:**
- Controlador: `app/Http/Controllers/Api/v1/AddressController.php:123-137`

---

## Flujos de Uso

### Flujo 1: Guardar Dirección en Perfil

**Escenario:** Usuario quiere guardar una dirección para usarla en futuros pedidos.

**Frontend:**
1. Cargar ubicaciones: `GET /api/v1/locations/cr` (sin autenticación)
2. Mostrar dropdowns en cascada:
   - Provincia → Canton → Distrito
3. Usuario completa formulario y guarda
4. Enviar a backend: `POST /api/v1/addresses` con **nombres** (strings)

**Ejemplo de request:**
```json
POST /api/v1/addresses
Authorization: Bearer {token}

{
  "label": "Casa",
  "province": "San José",
  "canton": "Escazú",
  "district": "San Rafael",
  "address_details": "100m norte de la iglesia",
  "is_default": true
}
```

**Backend:**
1. Valida que el usuario esté autenticado
2. Valida que la combinación provincia-cantón-distrito exista
3. Si es la primera dirección, la marca automáticamente como predeterminada
4. Si `is_default: true`, desmarca las demás
5. Guarda en tabla `addresses`

---

### Flujo 2: Crear Pedido con Dirección Guardada

**Escenario:** Usuario tiene direcciones guardadas y quiere usar una para su pedido.

**Frontend:**
1. Listar direcciones: `GET /api/v1/addresses`
2. Mostrar lista de direcciones al usuario
3. Usuario selecciona una dirección
4. Enviar pedido con `address_id`

**Ejemplo de request:**
```json
POST /api/v1/orders
Authorization: Bearer {token}

{
  "delivery_option": "delivery",
  "address_id": 5,
  "payment_method": "card",
  "items": [
    {
      "product_id": 10,
      "quantity": 2
    }
  ]
}
```

**Backend:**
1. Valida que `address_id` existe y pertenece al usuario autenticado
2. Obtiene la dirección de tabla `addresses`
3. Crea el pedido
4. Crea snapshot en `order_shipping_addresses` con los datos de la dirección

---

### Flujo 3: Crear Pedido con Dirección Temporal

**Escenario:** Usuario no tiene direcciones guardadas o quiere usar una dirección diferente solo para este pedido.

**Frontend:**
1. Cargar ubicaciones: `GET /api/v1/locations/cr`
2. Mostrar dropdowns en cascada
3. Usuario selecciona: Provincia → Cantón → Distrito
4. Enviar pedido con `shipping_address` usando **IDs** de `cr_locations`

**Ejemplo de request:**
```json
POST /api/v1/orders
Authorization: Bearer {token}

{
  "delivery_option": "delivery",
  "shipping_address": {
    "province_id": 1,
    "canton_id": 16,
    "district_id": 261,
    "address_details": "100m sur de la escuela"
  },
  "payment_method": "cash",
  "items": [
    {
      "product_id": 15,
      "quantity": 1
    }
  ]
}
```

**Backend:**
1. Valida que los IDs existan en `cr_locations`
2. Valida la jerarquía (cantón pertenece a provincia, distrito pertenece a cantón)
3. Obtiene los nombres de las ubicaciones desde `cr_locations`
4. Crea el pedido
5. Crea snapshot en `order_shipping_addresses` con los nombres

**Opcionalmente:** Frontend puede ofrecer checkbox "Guardar esta dirección" para crearla en `addresses` también.

---

## Seguridad y Autenticación

### Endpoints Públicos (sin autenticación)

```
✅ GET /api/v1/locations/cr
```

**Razón:** Son datos geográficos públicos de Costa Rica. No contiene información sensible.

**Ventajas:**
- Permite cargar dropdowns sin autenticación
- Mejora UX (usuario puede explorar antes de registrarse)
- Permite caché en frontend/CDN
- No expone datos de usuarios

---

### Endpoints Protegidos (requieren autenticación)

```
🔒 GET    /api/v1/addresses           (auth:sanctum, role:Cliente)
🔒 POST   /api/v1/addresses           (auth:sanctum, role:Cliente)
🔒 GET    /api/v1/addresses/{id}      (auth:sanctum, role:Cliente)
🔒 PUT    /api/v1/addresses/{id}      (auth:sanctum, role:Cliente)
🔒 DELETE /api/v1/addresses/{id}      (auth:sanctum, role:Cliente)
🔒 POST   /api/v1/addresses/{id}/set-default (auth:sanctum, role:Cliente)
🔒 POST   /api/v1/orders              (auth:sanctum, role:Cliente)
```

---

### Validaciones de Seguridad

#### 1. Ownership (Propiedad)
Todos los endpoints de direcciones usan el scope `forUser(auth()->id())` para asegurar que:
- Un usuario solo puede ver sus propias direcciones
- Un usuario solo puede modificar sus propias direcciones
- Un usuario solo puede eliminar sus propias direcciones

**Ejemplo en código:**
```php
// AddressController.php
$address = Address::forUser(auth()->id())->findOrFail($id);
```

#### 2. Validación de Ubicaciones

**Al guardar dirección (nombres):**
```php
CrLocation::locationExists($province, $canton, $district)
```

**Al crear pedido con dirección temporal (IDs):**
- Valida que cada ID existe en `cr_locations`
- Valida que el cantón pertenece a la provincia
- Valida que el distrito pertenece al cantón

#### 3. Validación de Dirección en Pedidos

```php
// Solo si address_id
Rule::exists('addresses', 'id')->where(function ($query) {
    $query->where('user_id', auth()->id());
})
```

Esto previene que un usuario use el `address_id` de otro usuario.

---

## Validaciones

### Validaciones en Direcciones Guardadas

**Campos requeridos:**
- `label`: "Casa", "Trabajo", "Otro"
- `province`: máximo 100 caracteres
- `canton`: máximo 100 caracteres
- `district`: máximo 100 caracteres
- `address_details`: máximo 500 caracteres

**Validación especial:**
- Combinación provincia-cantón-distrito debe existir en `cr_locations`

**Errores comunes:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "label": ["La etiqueta debe ser: Casa, Trabajo u Otro"],
    "district": ["La combinación de Provincia, Cantón y Distrito no es válida en Costa Rica"],
    "address_details": ["Las señas exactas son obligatorias"]
  }
}
```

---

### Validaciones en Pedidos con Dirección Temporal

**Reglas:**
- Para `delivery_option: "delivery"` debe haber **address_id O shipping_address** (no ambos)
- Los IDs de `shipping_address` deben existir en `cr_locations`
- Se valida la jerarquía geográfica:
  - El cantón debe pertenecer a la provincia seleccionada
  - El distrito debe pertenecer al cantón seleccionado

**Errores comunes:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "address": ["Para entregas a domicilio debe proporcionar address_id o shipping_address"],
    "shipping_address.canton_id": ["El cantón seleccionado no pertenece a la provincia"],
    "shipping_address.district_id": ["El distrito seleccionado no pertenece al cantón"]
  }
}
```

---

## Ejemplos de Implementación

### Frontend: Dropdowns en Cascada (React)

```jsx
import { useState, useEffect } from 'react';

function AddressForm() {
  const [locations, setLocations] = useState(null);
  const [selectedProvince, setSelectedProvince] = useState(null);
  const [selectedCanton, setSelectedCanton] = useState(null);
  const [selectedDistrict, setSelectedDistrict] = useState(null);

  // 1. Cargar ubicaciones al montar componente
  useEffect(() => {
    fetch('https://api.tudominio.com/api/v1/locations/cr')
      .then(res => res.json())
      .then(data => setLocations(data.provincias));
  }, []);

  // 2. Obtener cantones de la provincia seleccionada
  const cantones = selectedProvince?.cantones || [];

  // 3. Obtener distritos del cantón seleccionado
  const distritos = selectedCanton?.distritos || [];

  // 4. Manejar cambio de provincia
  const handleProvinceChange = (e) => {
    const province = locations.find(p => p.id === parseInt(e.target.value));
    setSelectedProvince(province);
    setSelectedCanton(null);
    setSelectedDistrict(null);
  };

  // 5. Manejar cambio de cantón
  const handleCantonChange = (e) => {
    const canton = cantones.find(c => c.id === parseInt(e.target.value));
    setSelectedCanton(canton);
    setSelectedDistrict(null);
  };

  // 6. Manejar cambio de distrito
  const handleDistrictChange = (e) => {
    const district = distritos.find(d => d.id === parseInt(e.target.value));
    setSelectedDistrict(district);
  };

  // 7. Submit para GUARDAR DIRECCIÓN (usa nombres)
  const handleSaveAddress = async (e) => {
    e.preventDefault();

    const addressData = {
      label: e.target.label.value,
      province: selectedProvince.nombre,  // String
      canton: selectedCanton.nombre,      // String
      district: selectedDistrict.nombre,  // String
      address_details: e.target.address_details.value,
      is_default: e.target.is_default.checked
    };

    const response = await fetch('https://api.tudominio.com/api/v1/addresses', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(addressData)
    });
  };

  // 8. Submit para CREAR PEDIDO (usa IDs)
  const handleCreateOrder = async (e) => {
    e.preventDefault();

    const orderData = {
      delivery_option: 'delivery',
      shipping_address: {
        province_id: selectedProvince.id,   // ID
        canton_id: selectedCanton.id,       // ID
        district_id: selectedDistrict.id,   // ID
        address_details: e.target.address_details.value
      },
      payment_method: 'card',
      items: [...] // tus items
    };

    const response = await fetch('https://api.tudominio.com/api/v1/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(orderData)
    });
  };

  return (
    <form onSubmit={handleSaveAddress}>
      {/* Etiqueta */}
      <select name="label" required>
        <option value="Casa">Casa</option>
        <option value="Trabajo">Trabajo</option>
        <option value="Otro">Otro</option>
      </select>

      {/* Provincia */}
      <select onChange={handleProvinceChange} required>
        <option value="">Seleccione provincia</option>
        {locations?.map(prov => (
          <option key={prov.id} value={prov.id}>
            {prov.nombre}
          </option>
        ))}
      </select>

      {/* Cantón */}
      <select
        onChange={handleCantonChange}
        disabled={!selectedProvince}
        required
      >
        <option value="">Seleccione cantón</option>
        {cantones.map(cant => (
          <option key={cant.id} value={cant.id}>
            {cant.nombre}
          </option>
        ))}
      </select>

      {/* Distrito */}
      <select
        onChange={handleDistrictChange}
        disabled={!selectedCanton}
        required
      >
        <option value="">Seleccione distrito</option>
        {distritos.map(dist => (
          <option key={dist.id} value={dist.id}>
            {dist.nombre}
          </option>
        ))}
      </select>

      {/* Señas exactas */}
      <textarea
        name="address_details"
        placeholder="Señas exactas..."
        maxLength={500}
        required
      />

      {/* Marcar como predeterminada */}
      <label>
        <input type="checkbox" name="is_default" />
        Dirección predeterminada
      </label>

      <button type="submit">Guardar Dirección</button>
    </form>
  );
}
```

---

### Backend: Uso del Modelo Address

```php
use App\Models\Address;

// Obtener todas las direcciones del usuario
$addresses = Address::forUser(auth()->id())->get();

// Obtener la dirección predeterminada
$defaultAddress = Address::forUser(auth()->id())
    ->default()
    ->first();

// Obtener dirección completa formateada
$address = Address::find(5);
echo $address->full_address;
// Output: "San José, Escazú, San Rafael. 100m norte de la iglesia"

// Convertir dirección a snapshot para pedido
$shippingData = $address->toShippingSnapshot();
// Output: ['province' => '...', 'canton' => '...', 'district' => '...', 'address_details' => '...']
```

---

## Comparación: Direcciones Guardadas vs Temporales

| Aspecto | Direcciones Guardadas | Direcciones Temporales |
|---------|----------------------|------------------------|
| **Tabla BD** | `addresses` | Solo snapshot en `order_shipping_addresses` |
| **Formato envío** | Nombres (strings) | IDs de `cr_locations` |
| **Reutilizable** | ✅ Sí | ❌ No |
| **Requiere autenticación** | ✅ Sí | ✅ Sí (para crear pedido) |
| **Validación** | `locationExists()` | Jerarquía de IDs |
| **Caso de uso** | Usuario frecuente | Compra única |
| **Propiedad** | Pertenece al usuario | Solo existe en el pedido |

---

## Diagrama de Flujo

```
┌─────────────────────────────────────────────────┐
│         GET /api/v1/locations/cr                │
│              (PÚBLICO)                          │
└──────────────────┬──────────────────────────────┘
                   │
                   ▼
       ┌───────────────────────┐
       │  Dropdowns Frontend   │
       │  Provincia → Cantón   │
       │  → Distrito           │
       └─────────┬─────────────┘
                 │
        ┌────────┴────────┐
        │                 │
        ▼                 ▼
┌───────────────┐  ┌──────────────┐
│   PERFIL      │  │   CHECKOUT   │
│               │  │              │
│ POST /addresses│  │ POST /orders │
│ (nombres)     │  │ (IDs o       │
│               │  │  address_id) │
└───────┬───────┘  └──────┬───────┘
        │                 │
        ▼                 ▼
┌───────────────┐  ┌──────────────┐
│ addresses     │  │ order_shipping│
│ (permanente)  │  │ _addresses    │
│               │  │ (snapshot)    │
└───────────────┘  └──────────────┘
```

---

## Preguntas Frecuentes

### ¿Por qué el endpoint de ubicaciones es público?

Las ubicaciones de Costa Rica son datos públicos y no contienen información sensible. Hacerlo público permite:
- Mejor UX (cargar dropdowns sin autenticación)
- Mejor performance (caché en frontend/CDN)
- Reducir carga en el servidor

### ¿Qué pasa si elimino la dirección predeterminada?

El sistema automáticamente asigna la primera dirección restante como nueva predeterminada.

### ¿Puedo tener múltiples direcciones predeterminadas?

No. El sistema garantiza que solo una dirección puede ser predeterminada por usuario. Al marcar una como predeterminada, las demás se desmarcan automáticamente.

### ¿Qué diferencia hay entre guardar en perfil vs dirección temporal en pedido?

- **Perfil**: La dirección se guarda permanentemente y puedes reutilizarla
- **Temporal**: Solo se usa para ese pedido específico, no se guarda en tu perfil

### ¿Por qué en perfil se usan nombres y en checkout IDs?

- **Perfil**: Usa nombres porque es más simple y legible en la BD
- **Checkout temporal**: Usa IDs para garantizar integridad referencial y validar jerarquía

### ¿Qué pasa si modifico una dirección guardada después de usarla en un pedido?

Nada. Los pedidos guardan un **snapshot** de la dirección en el momento de la compra. Los cambios posteriores no afectan pedidos antiguos.

---

## Archivos Relacionados

### Modelos
- `app/Models/Address.php`
- `app/Models/CrLocation.php`
- `app/Models/OrderShippingAddress.php`

### Controladores
- `app/Http/Controllers/Api/v1/AddressController.php`
- `app/Http/Controllers/Api/v1/LocationController.php`
- `app/Http/Controllers/Api/v1/ClientOrderController.php`

### Requests (Validaciones)
- `app/Http/Requests/v1/StoreAddressRequest.php`
- `app/Http/Requests/v1/UpdateAddressRequest.php`
- `app/Http/Requests/v1/StoreOnlineOrderRequest.php`

### Rutas
- `routes/v1/addresses.php`
- `routes/v1/locations.php`
- `routes/v1/orders.php`

### Migraciones
- `database/migrations/2025_11_15_153010_create_addresses_table.php`
- `database/migrations/2025_11_15_153706_create_cr_locations_table.php`
- `database/migrations/2025_11_11_031608_create_order_shipping_addresses_table.php`

---

**Última actualización:** 2025-11-21
