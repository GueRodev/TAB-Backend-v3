# CLAUDE.md - Contexto del Proyecto Backend TAB v3

**Última actualización:** 2025-11-16
**Versión del documento:** 3.0
**Stack:** Laravel 12 + PostgreSQL + Sanctum + Spatie Permissions

---

## 📋 INFORMACIÓN GENERAL DEL PROYECTO

**Nombre:** Backend API TAB v3
**Objetivo:** API RESTful para e-commerce con gestión de productos, pedidos y usuarios
**Frontend:** React + TypeScript + Vite (NO integrado aún)
**Base de datos:** PostgreSQL
**Autenticación:** Laravel Sanctum
**Gestión de roles:** Laravel Spatie Permissions

---

## 🎯 ESTADO ACTUAL DEL PROYECTO

### ✅ FASES COMPLETADAS

#### **FASE 1: Configuración Base de Laravel 12** ✅
- Laravel 12 instalado y funcional
- PostgreSQL configurado
- CORS configurado para frontend
- Laravel Sanctum instalado y configurado
- Estructura de carpetas establecida

#### **FASE 2: Sistema de Autenticación y Roles** ✅
- Laravel Spatie Permissions instalado
- Roles creados: **Super Admin**, **Moderador** y **Cliente**
- Tabla `users` migrada con campos: name, email, password, phone (nullable)
- Modelo User con trait `HasRoles`
- AuthController implementado:
  - `/api/v1/register` - Registro con asignación automática de rol Cliente
  - `/api/v1/login` - Login con retorno de roles y permisos
  - `/api/v1/logout` - Logout con revocación de tokens
- Middleware de autenticación y roles activo
- Rutas protegidas con `auth:sanctum` y `role:Super Admin`

#### **FASE 3: Gestión de Productos y Categorías** ✅

**Migraciones ejecutadas:**
- `2025_11_04_063009_create_categories_table.php`
- `2025_11_07_033919_create_products_table.php`
- `2025_11_07_035051_create_stock_movements_table.php`

**Modelos creados:**
- `Category` - Relaciones: belongsTo parent, hasMany children, hasMany products
- `Product` - Relaciones: belongsTo Category, hasMany StockMovement
- `StockMovement` - Relación belongsTo Product

**Controladores implementados:**
- `CategoryController` - CRUD completo de categorías con:
  - Sistema jerárquico (parent_id, level, order)
  - Categoría protegida "Otros" (is_protected = true)
  - Estrategia de reasignación de productos al eliminar categoría
  - Prevención de eliminación si es la única categoría (siempre debe existir "Otros")
  - Validaciones con Form Requests (StoreCategoryRequest, UpdateCategoryRequest)
  - Soft deletes con restauración
  - Reordenamiento de categorías

- `ProductController` - CRUD completo de productos con:
  - Sistema de imágenes (ProductImageService)
  - Control de stock con movimientos
  - Soft deletes
  - Validaciones con Form Requests (StoreProductRequest, UpdateProductRequest)

**Sistema de imágenes:**
- Disco configurado: `products` en `config/filesystems.php`
- Ruta de almacenamiento: `storage/app/public/products/`
- Enlace simbólico: `public/storage` → `storage/app/public`
- Servicio: `ProductImageService` para manejo de imágenes
- Formatos soportados: jpeg, png, jpg, webp (máx 2MB)

#### **FASE 4: Sistema de Pedidos** ✅

**Migraciones ejecutadas:**
- `create_orders_table.php` - Tabla de pedidos
- `create_order_items_table.php` - Items de pedidos
- `create_order_shipping_addresses_table.php` - Direcciones de envío (snapshot)

**Modelos creados:**
- `Order` - Relaciones: belongsTo User, hasMany OrderItem, hasOne OrderShippingAddress
- `OrderItem` - Relaciones: belongsTo Order, belongsTo Product
- `OrderShippingAddress` - Relación: belongsTo Order

**Servicio OrderService:**
- Manejo de lógica de negocio de pedidos
- Validación de stock antes de crear pedido
- Creación de pedidos con transacciones DB
- Actualización automática de stock
- Registro de movimientos de stock
- Snapshot de dirección de envío
- Cálculo automático de totales
- Soporte para dos tipos de pedidos:
  - `online` - Pedidos en línea (creados por clientes autenticados)
  - `store` - Pedidos en tienda (creados por Super Admin/Moderador)

**Controlador OrderController:**
- `index()` - Listar pedidos filtrados por rol
  - Cliente: solo sus pedidos
  - Super Admin/Moderador: todos los pedidos con filtros avanzados
- `store()` - Crear pedido (Online o Store)
- `show()` - Ver pedido específico con validación de permisos
- `updateStatus()` - Actualizar estado del pedido (Solo Super Admin/Moderador)
- `cancel()` - Cancelar pedido con devolución de stock

**Estados de pedidos:**
- `pending` - Pendiente
- `confirmed` - Confirmado
- `processing` - En proceso
- `shipped` - Enviado
- `delivered` - Entregado
- `cancelled` - Cancelado

**Form Requests:**
- `StoreOnlineOrderRequest` - Validación de pedidos online (dos opciones de dirección)
- `StoreStoreOrderRequest` - Validación de pedidos en tienda
- `UpdateOrderStatusRequest` - Validación de actualización de estado

**Características destacadas:**
- ✅ Validación de stock en tiempo real
- ✅ Transacciones DB para consistencia de datos
- ✅ Auto-completado de datos del cliente desde perfil
- ✅ Snapshot inmutable de dirección de envío
- ✅ Registro automático de movimientos de stock
- ✅ Try-catch con logging de errores
- ✅ Filtros avanzados (status, order_type, date_range, search)
- ✅ Envío automático de correo electrónico al completar pedido (online y store)
- ✅ Código documentado en español

**Sistema de Correos Electrónicos:**
- `OrderReceiptMail` - Mailable para comprobante de pedido
- Vista HTML profesional (`resources/views/emails/orders/receipt.blade.php`)
- Integrado en `OrderService::completeOrder()`
- Try-catch para errores sin afectar transacción
- Logging de errores de envío
- Contenido completo: información del pedido, productos, dirección, totales

#### **FASE 5A: Perfiles y Direcciones** ✅

**Migraciones ejecutadas:**
- `add_phone_to_users_table.php` - Agregar campo phone a users (nullable)
- `create_cr_locations_table.php` - Ubicaciones de Costa Rica (provincias, cantones, distritos)
- `create_addresses_table.php` - Direcciones guardadas de usuarios

**Modelos creados:**
- `CrLocation` - Modelo para ubicaciones de Costa Rica
- `Address` - Relación: belongsTo User, belongsTo CrLocation (province, canton, district)

**Características de CrLocation:**
- Estructura jerárquica: Provincia → Cantón → Distrito
- Método estático `getHierarchy()` para obtener árbol completo
- Caché de 24 horas para optimizar performance
- Retorna objetos con `{id, nombre}` para dropdowns en cascada

**Características de Address:**
- Método `toShippingSnapshot()` para crear snapshot inmutable
- Soft deletes
- Relación con User (un usuario puede tener múltiples direcciones)
- Resolución de nombres desde CrLocation

**Controladores:**
- `ProfileController` - Gestión de perfil del usuario autenticado
  - `show()` - Ver perfil con direcciones
  - `update()` - Actualizar perfil (name, email, phone, password)
- `AddressController` - CRUD de direcciones del usuario autenticado
  - Solo puede gestionar sus propias direcciones
  - Validación contra `cr_locations` table
- `CrLocationController` - Endpoint público para obtener ubicaciones

**Filosofía del campo phone:**
- ✅ **NO requerido en registro** - Mejor UX
- ✅ **Opcional en perfil** - Usuario decide cuándo agregarlo
- ✅ **Requerido al crear pedido online** - Se valida al checkout
- ✅ **Auto-completado desde perfil** - Si existe, se usa automáticamente
- ✅ **Snapshot temporal** - Si se proporciona `customer_phone` manualmente, no se guarda en perfil

#### **FASE 5B: User Management** ✅

**Objetivo:** CRUD de usuarios para Super Admin (gestión de Admin y Moderador, NO Cliente)

**Seeder creado:**
- `ModeradorRoleSeeder` - Crea rol Moderador con 12 permisos específicos:
  - view products, edit products, view categories
  - view all orders, create store orders, update order status, cancel orders
  - view all addresses, view own profile, edit own profile
  - view dashboard, view order reports

**Form Requests:**
- `StoreUserRequest` - Validación para crear usuarios Admin/Moderador
  - Solo Super Admin puede crear usuarios
  - Valida: name, email (unique), password (min 8, confirmed), role (Super Admin o Moderador)
  - **NO permite crear role Cliente** (solo registro público)
  - **NO requiere phone** (opcional)

- `UpdateUserRequest` - Validación para actualizar usuarios
  - Validaciones de seguridad:
    - Usuario no puede cambiar su propio rol
    - No se puede cambiar/eliminar el último Super Admin del sistema
  - Campos opcionales: name, email, password, role
  - Password opcional (solo si se desea cambiar)
  - Usa `Rule::when()` para validaciones condicionales

**Controlador UserController:**
- `index()` - Listar usuarios (Solo Super Admin)
- `store()` - Crear usuario Admin/Moderador con DB transaction
- `show()` - Ver usuario específico
- `update()` - Actualizar usuario con validaciones de seguridad y DB transaction
- `destroy()` - Eliminar usuario con validaciones:
  - No puede eliminar último Super Admin
  - No puede eliminarse a sí mismo
- Todos los métodos con try-catch y logging
- Código documentado en español

#### **INTEGRACIÓN PHASE 4 + PHASE 5** ✅

**Objetivo:** Integrar sistema de pedidos con direcciones y ubicaciones

**Características implementadas:**

1. **Dos opciones de dirección al crear pedido online:**

   **OPCIÓN A: Usar dirección guardada**
   ```json
   {
     "address_id": 4
   }
   ```
   - Usa `Address::toShippingSnapshot()`
   - Snapshot inmutable guardado en `order_shipping_addresses`

   **OPCIÓN B: Campos manuales de ubicación**
   ```json
   {
     "shipping_address": {
       "province_id": 1,
       "canton_id": 104,
       "district_id": 40,
       "address_details": "Del súper, 200m oeste"
     }
   }
   ```
   - Valida jerarquía: cantón pertenece a provincia, distrito pertenece a cantón
   - Valida contra tabla `cr_locations`
   - Resuelve nombres desde IDs para snapshot
   - Snapshot inmutable guardado en `order_shipping_addresses`

2. **Validación de jerarquía de ubicaciones:**
   - Método `validateLocationHierarchy()` en StoreOnlineOrderRequest
   - Compara `province_id` y `canton_id` de los registros
   - Valida tipos de ubicación (canton, district)
   - Mensajes de error en español

3. **Resolución de nombres de ubicaciones:**
   - Método `prepareShippingAddress()` en OrderService
   - Usa columnas `province_name`, `canton_name`, `district_name`
   - Snapshot con nombres legibles (no IDs)

4. **Auto-completado de datos del cliente:**
   - Método `prepareCustomerData()` en OrderService
   - Usa operador `??` para fallback a datos del perfil:
     - `customer_name ?? $user->name`
     - `customer_email ?? $user->email`
     - `customer_phone ?? $user->phone`
   - Si `customer_phone` se proporciona, es solo snapshot (no se guarda en perfil)

5. **Testing completo:**
   - ✅ Pedido con `address_id` (dirección guardada)
   - ✅ Pedido con campos manuales de ubicación
   - ✅ Validación de teléfono (requerido al crear pedido si no está en perfil)
   - ✅ Auto-completado de datos desde perfil

---

## 📂 ESTRUCTURA DEL PROYECTO

```
Backend-Api-TAB-v3/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── v1/
│   │   │           ├── AuthController.php
│   │   │           ├── CategoryController.php
│   │   │           ├── ProductController.php
│   │   │           ├── OrderController.php
│   │   │           ├── ProfileController.php
│   │   │           ├── AddressController.php
│   │   │           ├── CrLocationController.php
│   │   │           └── UserController.php
│   │   ├── Requests/
│   │   │   └── v1/
│   │   │       ├── StoreCategoryRequest.php
│   │   │       ├── UpdateCategoryRequest.php
│   │   │       ├── StoreProductRequest.php
│   │   │       ├── UpdateProductRequest.php
│   │   │       ├── StoreOnlineOrderRequest.php
│   │   │       ├── StoreStoreOrderRequest.php
│   │   │       ├── UpdateOrderStatusRequest.php
│   │   │       ├── UpdateProfileRequest.php
│   │   │       ├── StoreAddressRequest.php
│   │   │       ├── UpdateAddressRequest.php
│   │   │       ├── StoreUserRequest.php
│   │   │       └── UpdateUserRequest.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php (con HasRoles trait)
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── StockMovement.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── OrderShippingAddress.php
│   │   ├── CrLocation.php
│   │   └── Address.php
│   └── Services/
│       ├── ProductImageService.php
│       └── OrderService.php
├── config/
│   ├── cors.php (configurado)
│   ├── sanctum.php (configurado)
│   └── filesystems.php (disco 'products' configurado)
├── database/
│   ├── migrations/
│   │   ├── 2025_10_24_050549_create_personal_access_tokens_table.php
│   │   ├── 2025_10_27_053754_create_permission_tables.php (Spatie)
│   │   ├── 2025_11_04_063009_create_categories_table.php
│   │   ├── 2025_11_07_033919_create_products_table.php
│   │   ├── 2025_11_07_035051_create_stock_movements_table.php
│   │   ├── create_orders_table.php
│   │   ├── create_order_items_table.php
│   │   ├── create_order_shipping_addresses_table.php
│   │   ├── add_phone_to_users_table.php
│   │   ├── create_cr_locations_table.php
│   │   └── create_addresses_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── ModeradorRoleSeeder.php
├── routes/
│   ├── api.php
│   └── v1/
│       ├── auth.php
│       ├── categories.php
│       ├── products.php
│       ├── orders.php
│       ├── profile.php
│       ├── addresses.php
│       ├── locations.php
│       └── users.php
├── storage/
│   └── app/
│       └── public/
│           └── products/ (imágenes de productos)
└── docs/
    ├── Plan-Context.md (plan de implementación original)
    └── CLAUDE.md (este documento)
```

---

## 🔐 SISTEMA DE ROLES Y PERMISOS

### Roles Actuales

| Rol | Descripción | Asignación |
|-----|-------------|------------|
| **Super Admin** | Administrador completo del sistema | Manual (seeder) |
| **Moderador** | Administrador con permisos limitados | Creado por Super Admin |
| **Cliente** | Usuario final que compra | Automática en registro |

### Permisos del Rol Moderador

El rol **Moderador** tiene 12 permisos específicos:

```php
[
    'view products',        // Ver productos
    'edit products',        // Editar productos
    'view categories',      // Ver categorías
    'view all orders',      // Ver todos los pedidos
    'create store orders',  // Crear pedidos en tienda
    'update order status',  // Actualizar estado de pedidos
    'cancel orders',        // Cancelar pedidos
    'view all addresses',   // Ver todas las direcciones
    'view own profile',     // Ver su propio perfil
    'edit own profile',     // Editar su propio perfil
    'view dashboard',       // Acceder al dashboard
    'view order reports',   // Ver reportes de pedidos
]
```

### Permisos por Módulo

| Acción | Super Admin | Moderador | Cliente |
|--------|-------------|-----------|---------|
| **Productos** |
| Ver productos | ✅ | ✅ | ✅ |
| Crear productos | ✅ | ❌ | ❌ |
| Editar productos | ✅ | ✅ | ❌ |
| Eliminar productos | ✅ | ❌ | ❌ |
| **Categorías** |
| Ver categorías | ✅ | ✅ | ✅ |
| Crear/Editar/Eliminar categorías | ✅ | ❌ | ❌ |
| **Pedidos** |
| Ver todos los pedidos | ✅ | ✅ | ❌ |
| Ver propios pedidos | ✅ | ✅ | ✅ |
| Crear pedidos online | ✅ | ✅ | ✅ |
| Crear pedidos en tienda | ✅ | ✅ | ❌ |
| Actualizar estado | ✅ | ✅ | ❌ |
| Cancelar pedidos | ✅ | ✅ | ❌ |
| **Usuarios** |
| Gestionar usuarios (Admin/Moderador) | ✅ | ❌ | ❌ |
| Ver perfil propio | ✅ | ✅ | ✅ |
| Editar perfil propio | ✅ | ✅ | ✅ |
| **Direcciones** |
| Ver todas las direcciones | ✅ | ✅ | ❌ |
| Gestionar propias direcciones | ✅ | ✅ | ✅ |

---

## 🗄️ ESQUEMA DE BASE DE DATOS ACTUAL

### Tabla: `users`
```sql
- id (bigint, PK)
- name (string)
- email (string, unique)
- password (string)
- phone (string, nullable) - Agregado en Fase 5A
- email_verified_at (timestamp, nullable)
- created_at, updated_at
```

### Tabla: `categories`
```sql
- id (bigint, PK)
- parent_id (bigint, FK → categories.id, nullable, cascade on delete)
- level (tinyint, default 0, rango 0-3)
- name (string, unique)
- slug (string, unique)
- description (text, nullable)
- order (integer, default 0)
- is_protected (boolean, default false)
- is_active (boolean, default true)
- deleted_at (timestamp, nullable)
- created_at, updated_at

ÍNDICES:
- index(['level', 'order'])
```

### Tabla: `products`
```sql
- id (bigint, PK)
- category_id (bigint, FK → categories.id)
- name (string)
- slug (string, unique)
- brand (string, nullable)
- description (text, nullable)
- price (decimal 10,2)
- stock (integer, default 0)
- sku (string, unique, nullable)
- image_url (string, nullable)
- status (enum: active, inactive, out_of_stock)
- is_featured (boolean, default false)
- deleted_at (timestamp, nullable)
- created_at, updated_at
```

### Tabla: `stock_movements`
```sql
- id (bigint, PK)
- product_id (bigint, FK → products.id, cascade on delete)
- user_id (bigint, FK → users.id, nullable)
- type (enum: entrada, salida, ajuste, pedido)
- quantity (integer)
- stock_before (integer)
- stock_after (integer)
- reason (string, nullable)
- created_at, updated_at
```

### Tabla: `orders`
```sql
- id (bigint, PK)
- user_id (bigint, FK → users.id, nullable)
- order_number (string, unique)
- order_type (enum: online, store)
- status (enum: pending, confirmed, processing, shipped, delivered, cancelled)
- customer_name (string)
- customer_phone (string)
- customer_email (string)
- delivery_option (enum: delivery, pickup)
- payment_method (string)
- subtotal (decimal 10,2)
- shipping_cost (decimal 10,2, default 0)
- total (decimal 10,2)
- notes (text, nullable)
- created_at, updated_at
```

### Tabla: `order_items`
```sql
- id (bigint, PK)
- order_id (bigint, FK → orders.id, cascade on delete)
- product_id (bigint, FK → products.id)
- product_name (string) - Snapshot
- product_sku (string, nullable) - Snapshot
- product_description (text, nullable) - Snapshot
- product_image_url (string, nullable) - Snapshot
- quantity (integer)
- price_at_purchase (decimal 10,2) - Snapshot
- subtotal (decimal 10,2)
- created_at, updated_at
```

### Tabla: `order_shipping_addresses`
```sql
- id (bigint, PK)
- order_id (bigint, FK → orders.id, cascade on delete, unique)
- province (string) - Snapshot (nombre, no ID)
- canton (string) - Snapshot (nombre, no ID)
- district (string) - Snapshot (nombre, no ID)
- address_details (text)
- created_at, updated_at
```

### Tabla: `cr_locations`
```sql
- id (bigint, PK)
- province_id (integer)
- canton_id (integer, nullable)
- province_name (string)
- canton_name (string, nullable)
- district_name (string, nullable)
- type (enum: province, canton, district)
- created_at, updated_at

ÍNDICES:
- index(['type'])
- index(['province_id', 'canton_id'])
```

### Tabla: `addresses`
```sql
- id (bigint, PK)
- user_id (bigint, FK → users.id, cascade on delete)
- province_id (bigint, FK → cr_locations.id)
- canton_id (bigint, FK → cr_locations.id)
- district_id (bigint, FK → cr_locations.id)
- address_details (text)
- deleted_at (timestamp, nullable)
- created_at, updated_at
```

---

## 📊 ENDPOINTS DISPONIBLES

### Autenticación (Públicos)
- `POST /api/v1/register` - Registro de usuario (asigna rol Cliente automáticamente)
- `POST /api/v1/login` - Iniciar sesión
- `POST /api/v1/logout` - Cerrar sesión (requiere auth:sanctum)

### Productos (Públicos)
- `GET /api/v1/products` - Listar productos con filtros
- `GET /api/v1/products/featured` - Productos destacados
- `GET /api/v1/products/{id}` - Ver producto

### Productos (Super Admin)
- `POST /api/v1/products` - Crear producto con imagen
- `PUT /api/v1/products/{id}` - Actualizar producto con imagen
- `DELETE /api/v1/products/{id}` - Eliminar producto (soft delete)
- `DELETE /api/v1/products/{id}/force` - Eliminar permanentemente
- `POST /api/v1/products/{id}/restore` - Restaurar producto
- `POST /api/v1/products/{id}/stock` - Ajustar stock

### Categorías (Públicos)
- `GET /api/v1/categories` - Listar categorías con jerarquía
- `GET /api/v1/categories/{id}` - Ver categoría con productos

### Categorías (Super Admin)
- `POST /api/v1/categories` - Crear categoría
- `PUT /api/v1/categories/{id}` - Actualizar categoría
- `PUT /api/v1/categories/reorder` - Reordenar múltiples categorías
- `DELETE /api/v1/categories/{id}` - Eliminar categoría (soft delete con reasignación)
- `DELETE /api/v1/categories/{id}/force` - Eliminar permanentemente (con reasignación)
- `POST /api/v1/categories/{id}/restore` - Restaurar categoría

### Pedidos (Autenticados)
- `GET /api/v1/orders` - Listar pedidos (filtrado por rol)
  - Cliente: solo sus pedidos
  - Super Admin/Moderador: todos los pedidos con filtros
- `GET /api/v1/orders/{id}` - Ver pedido específico
- `POST /api/v1/orders` - Crear pedido online (solo usuarios autenticados)
  - Acepta `address_id` o campos manuales de ubicación
  - Auto-completa datos del cliente desde perfil
  - Valida teléfono (requerido)

### Pedidos (Super Admin/Moderador)
- `POST /api/v1/orders/store` - Crear pedido en tienda
- `PATCH /api/v1/orders/{id}/status` - Actualizar estado
- `DELETE /api/v1/orders/{id}` - Cancelar pedido (devuelve stock)

### Perfil (Autenticado)
- `GET /api/v1/profile` - Ver perfil con direcciones
- `PATCH /api/v1/profile` - Actualizar perfil (name, email, phone, password)

### Direcciones (Autenticado)
- `GET /api/v1/addresses` - Listar direcciones del usuario
- `GET /api/v1/addresses/{id}` - Ver dirección específica
- `POST /api/v1/addresses` - Crear dirección
- `PUT /api/v1/addresses/{id}` - Actualizar dirección
- `DELETE /api/v1/addresses/{id}` - Eliminar dirección (soft delete)

### Ubicaciones (Público)
- `GET /api/v1/cr-locations` - Obtener jerarquía completa de ubicaciones
  - Provincias → Cantones → Distritos
  - Caché de 24 horas

### Usuarios (Super Admin)
- `GET /api/v1/users` - Listar usuarios
- `GET /api/v1/users/{id}` - Ver usuario específico
- `POST /api/v1/users` - Crear usuario Admin/Moderador
- `PUT /api/v1/users/{id}` - Actualizar usuario
- `DELETE /api/v1/users/{id}` - Eliminar usuario

---

## 🛠️ FUNCIONALIDADES IMPLEMENTADAS

### Sistema de Autenticación (Sanctum)
- Registro de usuarios con asignación automática de rol Cliente
- Login con generación de token
- Logout con revocación de token
- Middleware `auth:sanctum` para rutas protegidas
- Middleware `role:Super Admin` para rutas administrativas
- Teléfono opcional en registro (mejor UX)

### Gestión de Categorías
- CRUD completo con validaciones avanzadas
- Sistema jerárquico de 4 niveles (0-3)
- Categoría protegida "Otros" (is_protected = true)
- Estrategia de reasignación automática de productos al eliminar
- Validaciones:
  - Prevenir eliminación de categorías protegidas
  - Prevenir modificación de categorías protegidas
  - Prevenir referencias circulares
  - Validar que parent tenga level menor
- Soft deletes con posibilidad de restaurar
- Reordenamiento de categorías
- Conteo de productos por categoría

### Gestión de Productos
- CRUD completo con validaciones
- Sistema de imágenes con `ProductImageService`
- Filtros avanzados: categoría, marca, estado, precio, búsqueda
- Productos destacados
- Soft deletes con posibilidad de restaurar
- Sistema de stock con historial de movimientos

### Sistema de Pedidos
- Dos tipos de pedidos: `online` y `store`
- Validación de stock en tiempo real
- Creación de pedidos con transacciones DB
- Actualización automática de stock
- Registro de movimientos de stock
- Snapshot de productos (nombre, precio, SKU, etc.)
- Snapshot de dirección de envío
- Cálculo automático de totales
- Estados: pending, confirmed, processing, shipped, delivered, cancelled
- Cancelación con devolución de stock
- Filtros avanzados por estado, tipo, rango de fechas
- **Envío automático de correo electrónico:**
  - Comprobante de pedido enviado al completar pedido
  - Vista HTML profesional y responsive
  - Funciona para pedidos online y en tienda física
  - Manejo de errores sin afectar transacción principal

### Gestión de Perfiles y Direcciones
- Actualización de perfil (name, email, phone, password)
- Teléfono opcional (no requerido en registro)
- CRUD de direcciones guardadas
- Validación contra ubicaciones de Costa Rica
- Snapshot inmutable al crear pedido

### Sistema de Ubicaciones de Costa Rica
- Jerarquía completa: Provincias → Cantones → Distritos
- Endpoint público para obtener ubicaciones
- Caché de 24 horas para optimizar performance
- Listo para dropdowns en cascada en frontend

### Gestión de Usuarios (Super Admin)
- CRUD de usuarios Admin y Moderador
- Rol Moderador con 12 permisos específicos
- Validaciones de seguridad:
  - Usuario no puede cambiar su propio rol
  - No se puede eliminar el último Super Admin
  - Usuario no puede eliminarse a sí mismo
- Transacciones DB para consistencia
- Try-catch con logging
- Código documentado en español

---

## 🚧 FASES PENDIENTES

### FASE 6: SISTEMA DE NOTIFICACIONES

**Objetivo:**
Implementar notificaciones persistentes para administradores sin tiempo real (polling).

### Arquitectura Propuesta
**Enfoque:** Backend con polling (SIN Laravel Broadcasting)
- Notificaciones persisten en base de datos
- Frontend consulta cada 30 segundos
- No requiere Pusher ni WebSockets
- Escalable a tiempo real en el futuro

### Tipos de Notificaciones

| Tipo | Descripción | Cuándo se genera | Destinatario |
|------|-------------|------------------|--------------|
| `order_created` | Pedido en tienda creado | Cliente crea pedido | Super Admin |
| `order_archived` | Pedido archivado | Admin archiva pedido | Super Admin |
| `user_deactivated` | Cliente desactivado | Admin desactiva usuario | Super Admin |
| `user_activated` | Cliente activado | Admin activa usuario | Super Admin |

### Subtareas Pendientes

1. **Migración de tabla notifications**
   - Campos: id (UUID), type, title, message, user_id, data (JSON), read_at, timestamps
   - Índices en user_id y created_at para performance

2. **Modelo Notification con Eloquent**
   - Relación belongsTo con User
   - Scopes: unread(), byType(), recent()
   - Métodos: markAsRead(), markAsUnread()

3. **NotificationController**
   - GET /api/notifications - Listar notificaciones
   - GET /api/notifications/unread - Solo no leídas
   - GET /api/notifications/unread/count - Contador para badge
   - PATCH /api/notifications/{id}/read - Marcar como leída
   - PATCH /api/notifications/read-all - Marcar todas como leídas
   - DELETE /api/notifications/{id} - Eliminar notificación

4. **Integración en Controllers existentes**
   - OrderController: Crear notificación en store()
   - Helper: NotificationService para centralizar lógica

5. **Frontend: Actualizar NotificationsContext**
   - Implementar polling cada 30 segundos
   - Consultar endpoint /api/notifications/unread
   - Actualizar badge con contador

---

### FASE 7: DASHBOARD Y ANALYTICS

**Objetivo:**
Implementar dashboard administrativo con métricas, estadísticas y reportes de ventas.

#### Lógica Actual Reutilizable ✅

**Modelos y relaciones existentes:**
- `Order` con relaciones (items, user, shippingAddress) - Listo para consultas agregadas
- `OrderItem` con snapshots de productos - Histórico de ventas por producto
- `Product` con stock y movimientos - Análisis de inventario
- `StockMovement` con tipos y razones - Trazabilidad completa
- `Category` con jerarquía - Análisis por categoría
- `User` con roles - Segmentación de clientes
- `CrLocation` - Análisis geográfico de ventas

**Filtros y scopes existentes:**
- Filtros por fecha en OrderController (`created_at`)
- Filtros por estado, tipo, método de pago
- Filtros por email de cliente
- Paginación implementada

**Funcionalidades existentes:**
- Cálculo de totales (subtotal, shipping, total)
- Conteo de productos por categoría
- Historial de movimientos de stock
- Snapshots de productos en pedidos (precios históricos)

#### Funcionalidades Nuevas a Implementar 🆕

**1. DashboardController**
- `GET /api/v1/dashboard/metrics` - Métricas generales del dashboard
  ```json
  {
    "today_sales": 150000,
    "today_orders": 12,
    "week_sales": 890000,
    "month_sales": 3200000,
    "total_customers": 245,
    "pending_orders": 8,
    "low_stock_products": 5
  }
  ```

- `GET /api/v1/dashboard/sales-by-period` - Ventas por período (día, semana, mes, año)
  - Filtros: date_from, date_to, group_by (day, week, month)
  - Retorna array para gráficos de tendencias

- `GET /api/v1/dashboard/top-products` - Productos más vendidos
  - Filtros: limit (default 10), date_from, date_to
  - Agrupa por product_id, suma quantities
  - Retorna: product_name, total_quantity, total_revenue

- `GET /api/v1/dashboard/sales-by-category` - Ventas por categoría
  - Agrupa por category_id desde order_items
  - Retorna: category_name, total_orders, total_revenue

- `GET /api/v1/dashboard/sales-by-location` - Ventas por ubicación
  - Agrupa por province desde order_shipping_addresses
  - Retorna: province, total_orders, total_revenue

- `GET /api/v1/dashboard/low-stock` - Productos con stock bajo
  - Filtros: threshold (default 10)
  - Retorna productos donde stock < threshold

- `GET /api/v1/dashboard/payment-methods` - Distribución de métodos de pago
  - Agrupa por payment_method
  - Retorna: payment_method, count, total_revenue

- `GET /api/v1/dashboard/order-status` - Distribución de estados de pedidos
  - Agrupa por status
  - Retorna: status, count

**2. ReportController**
- `GET /api/v1/reports/sales` - Reporte detallado de ventas
  - Filtros: date_from, date_to, order_type, payment_method, export (pdf, excel)
  - Retorna listado completo de pedidos con totales agregados

- `GET /api/v1/reports/products` - Reporte de productos vendidos
  - Filtros: date_from, date_to, category_id
  - Agrupa por product_id
  - Retorna: product_name, quantity_sold, revenue, stock_remaining

- `GET /api/v1/reports/stock-movements` - Historial de movimientos de stock
  - Filtros: date_from, date_to, product_id, type
  - Retorna listado de movimientos con detalles

- `GET /api/v1/reports/customers` - Reporte de clientes
  - Filtros: date_from, date_to
  - Retorna: customer_email, total_orders, total_spent, last_order_date

**3. DashboardService**
- Métodos helper para cálculos complejos
- Caché de métricas (cache por 5-10 minutos)
- Métodos:
  - `calculateSalesByPeriod($dateFrom, $dateTo, $groupBy)`
  - `getTopSellingProducts($limit, $dateFrom, $dateTo)`
  - `getSalesByCategory($dateFrom, $dateTo)`
  - `getLowStockProducts($threshold)`
  - `getCustomerMetrics($dateFrom, $dateTo)`

**4. Optimizaciones Necesarias**
- **Índices de BD:**
  ```sql
  -- Índice en created_at de orders para consultas por fecha
  CREATE INDEX idx_orders_created_at ON orders(created_at);

  -- Índice en status de orders para filtros
  CREATE INDEX idx_orders_status ON orders(status);

  -- Índice compuesto para consultas frecuentes
  CREATE INDEX idx_orders_created_status ON orders(created_at, status);
  ```

- **Eager Loading:**
  - Usar `with()` para evitar N+1 queries
  - Cargar relaciones necesarias en una sola consulta

- **Queries Agregadas:**
  - Usar `selectRaw()`, `groupBy()`, `sum()`, `count()` directamente en BD
  - Evitar cargar todos los pedidos en memoria

- **Caché:**
  - Cachear métricas del dashboard por 5-10 minutos
  - Invalidar caché al completar/cancelar pedidos
  - Usar tags de caché para invalidación selectiva

**5. Middlewares y Permisos**
- Solo Super Admin y Moderador pueden acceder al dashboard
- Middleware: `role:Super Admin|Moderador`
- Permisos ya existentes en Moderador:
  - `view dashboard` ✅
  - `view order reports` ✅

**6. Frontend (Opcional - Fase de Integración)**
- Charts.js o Recharts para gráficos
- Tablas con paginación y filtros
- Exportación de reportes a PDF/Excel
- Actualización automática de métricas

#### Estimación de Complejidad

| Componente | Complejidad | Reutilización |
|------------|-------------|---------------|
| DashboardController | Media | 60% - Usa modelos y filtros existentes |
| ReportController | Media | 70% - Usa queries similares a existentes |
| DashboardService | Alta | 40% - Lógica nueva de agregación |
| Optimizaciones BD | Baja | N/A - Índices simples |
| Caché | Baja | 80% - Usar sistema de caché de Laravel |
| Frontend | Alta | 30% - Requiere componentes nuevos |

**Total:** Se puede reutilizar aproximadamente **60% de la lógica existente**. El 40% restante son queries agregadas, cálculos y optimizaciones nuevas.

---

## 📝 NOTAS TÉCNICAS IMPORTANTES

### Filosofía del Campo Phone
**Decisión de diseño:** El teléfono NO es requerido en el registro para mejorar la experiencia del usuario.

- ✅ **NO requerido en registro** - Mejor UX, menos fricción
- ✅ **Opcional en perfil** - Usuario decide cuándo agregarlo
- ✅ **Requerido al crear pedido online** - Se valida en checkout
- ✅ **Auto-completado desde perfil** - Si existe, se usa automáticamente
- ✅ **Snapshot temporal** - `customer_phone` proporcionado manualmente no se guarda en perfil

**Flujo de uso:**
1. Cliente se registra sin teléfono → `phone: null`
2. Al crear pedido:
   - Si tiene phone en perfil → se usa automáticamente
   - Si NO tiene phone en perfil → debe proporcionar `customer_phone` en request
3. Si proporciona `customer_phone`, es solo para ese pedido (no actualiza perfil)
4. Si desea guardarlo, debe ir a su perfil y actualizarlo

### Sistema de Snapshots
**Concepto:** Datos inmutables congelados al momento de la compra

**Implementado en:**
1. **OrderItem** - Snapshot de producto:
   - `product_name`, `product_sku`, `product_description`, `product_image_url`, `price_at_purchase`
   - Garantiza que los datos del producto en el pedido no cambien aunque se edite el producto después

2. **OrderShippingAddress** - Snapshot de dirección:
   - `province`, `canton`, `district`, `address_details` (todos como strings, NO IDs)
   - Garantiza que la dirección del pedido no cambie aunque el usuario edite/elimine la dirección guardada

**Métodos:**
- `Address::toShippingSnapshot()` - Convierte Address a array para snapshot
- `OrderService::prepareShippingAddress()` - Prepara snapshot desde address_id o campos manuales

### Validación de Jerarquía de Ubicaciones
**Desafío:** Validar que cantón pertenece a provincia y distrito pertenece a cantón.

**Solución implementada:**
- `validateLocationHierarchy()` en StoreOnlineOrderRequest
- Compara columnas `province_id` y `canton_id` de registros en `cr_locations`
- Valida tipos de ubicación (`canton`, `district`)
- Mensajes de error en español

**Estructura de cr_locations:**
```
Provincia:  id=1, province_id=1, canton_id=null, type='province'
Cantón:     id=104, province_id=1, canton_id=104, type='canton'
Distrito:   id=40, province_id=1, canton_id=104, type='district'
```

### Form-Data con Laravel
- Laravel no soporta directamente `multipart/form-data` con PUT/PATCH
- **Solución implementada:** Usar POST con `_method=PUT` (method spoofing)
- El método `prepareForValidation()` se ejecuta ANTES de `rules()`
- Conversión de booleanos necesaria para datos desde form-data

### Transacciones DB
**Cuándo usar DB::transaction():**
- Cuando múltiples operaciones deben ser atómicas
- Ejemplos implementados:
  - `OrderService::createOnlineOrder()` - Crear order + items + shipping address + stock movements
  - `UserController::store()` - Crear usuario + asignar rol
  - `UserController::update()` - Actualizar datos + cambiar rol

### Try-Catch y Logging
**Patrón implementado en todos los controladores:**
```php
try {
    // Lógica de negocio
    return response()->json(['success' => true, 'data' => ...], 200);
} catch (Exception $e) {
    Log::error('Descripción del error: ' . $e->getMessage());
    return response()->json([
        'success' => false,
        'message' => 'Mensaje amigable para el usuario',
        'error' => config('app.debug') ? $e->getMessage() : null
    ], 500);
}
```

**Ventajas:**
- Errores se registran en logs para debugging
- Mensajes de error amigables para usuarios
- Detalles técnicos solo se muestran en modo debug
- Respuestas JSON consistentes con flag `success`

---

## 🔧 COMANDOS ÚTILES

```bash
# Ejecutar migraciones
php artisan migrate

# Crear migración
php artisan make:migration create_notifications_table

# Crear modelo con migración y controlador
php artisan make:model Notification -mc

# Crear Form Request
php artisan make:request StoreNotificationRequest

# Crear seeder
php artisan make:seeder ModeradorRoleSeeder

# Ejecutar seeder específico
php artisan db:seed --class=ModeradorRoleSeeder

# Crear enlace simbólico de storage
php artisan storage:link

# Ejecutar todos los seeders
php artisan db:seed

# Ver rutas
php artisan route:list

# Ver rutas filtradas por nombre
php artisan route:list --name=orders

# Rollback última migración
php artisan migrate:rollback

# Refrescar todas las migraciones (⚠️ elimina datos)
php artisan migrate:fresh

# Refrescar migraciones y ejecutar seeders
php artisan migrate:fresh --seed

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 🔍 REFERENCIAS

- **Plan original:** `docs/Plan-Context.md`
- **Documentación Laravel 12:** https://laravel.com/docs/12.x
- **Spatie Permissions:** https://spatie.be/docs/laravel-permission/v6
- **Laravel Sanctum:** https://laravel.com/docs/12.x/sanctum
- **PostgreSQL Docs:** https://www.postgresql.org/docs/

---

## 📈 RESUMEN DE PROGRESO

| Fase | Estado | Porcentaje |
|------|--------|-----------|
| Fase 1: Configuración Base | ✅ Completada | 100% |
| Fase 2: Autenticación y Roles | ✅ Completada | 100% |
| Fase 3: Productos y Categorías | ✅ Completada | 100% |
| Fase 4: Sistema de Pedidos + Correos | ✅ Completada | 100% |
| Fase 5A: Perfiles y Direcciones | ✅ Completada | 100% |
| Fase 5B: User Management | ✅ Completada | 100% |
| **Integración Phase 4 + 5** | ✅ Completada | 100% |
| Fase 6: Sistema de Notificaciones | ⏳ Pendiente | 0% |
| Fase 7: Dashboard y Analytics | ⏳ Pendiente | 0% |

**Progreso total del proyecto:** 78% completado (7 de 9 fases)

---

**Documento actualizado:** 2025-11-16
**Mantenido por:** Claude
**Versión:** 3.0
