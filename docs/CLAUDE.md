# CLAUDE.md - Contexto del Proyecto Backend TAB v3

**Última actualización:** 2025-11-09
**Versión del documento:** 1.0
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
- Roles creados: **Super Admin** y **Cliente**
- Tabla `users` migrada con campos: name, email, password
  - **NOTA:** El campo `phone` NO está en el modelo User. Se implementará en la migración `addresses` en la Fase 5.
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

**Form Requests de Categorías:**

- **StoreCategoryRequest:**
  - Validaciones: name (required, unique), parent_id (nullable, exists), level (0-3), description, order, is_active
  - Validación personalizada: El parent debe tener un level menor al de la categoría hija

- **UpdateCategoryRequest:**
  - Validaciones: name (required, unique excepto actual), parent_id (nullable, exists), level (0-3), description, order, is_active
  - Validación personalizada:
    - Previene modificar categorías protegidas (is_protected = true)
    - Valida que el parent tenga level menor
    - Valida que NO se creen referencias circulares (una categoría no puede ser su propio ancestro)

**Form Requests de Productos:**

- **StoreProductRequest:**
  - Validaciones: name, category_id, brand, description, price, stock, sku (unique), image (jpeg/png/jpg/webp, max 2MB), status, is_featured
  - Método `prepareForValidation()`: Convierte is_featured de string a boolean (acepta: 0, 1, true, false)

- **UpdateProductRequest:**
  - Validaciones: name (unique excepto actual), category_id, brand, description, price, stock, sku (unique excepto actual), image, status, is_featured
  - Método `prepareForValidation()`: Convierte is_featured de string a boolean

**Estrategia de Eliminación de Categorías:**
1. **Categoría protegida "Otros"**: Siempre debe existir (is_protected = true)
2. **Al eliminar una categoría**:
   - Si la categoría tiene productos asignados, se reasignan automáticamente a "Otros"
   - No se puede eliminar la categoría protegida "Otros"
   - Nunca puede haber 0 categorías en el sistema
3. **Validaciones**:
   - Previene eliminar categorías protegidas (HTTP 403)
   - Verifica existencia de categoría "Otros" (HTTP 500 si no existe)
   - Informa cuántos productos fueron reasignados

**Sistema de imágenes:**
- Disco configurado: `products` en `config/filesystems.php`
- Ruta de almacenamiento: `storage/app/public/products/`
- Enlace simbólico: `public/storage` → `storage/app/public`
- Servicio: `ProductImageService` para manejo de imágenes
- Formatos soportados: jpeg, png, jpg, webp (máx 2MB)

**Rutas API v1:**
- **Públicas (GET):**
  - `/api/v1/products` - Listar productos con filtros
  - `/api/v1/products/featured` - Productos destacados
  - `/api/v1/products/{id}` - Ver producto específico
  - `/api/v1/categories` - Listar categorías con hijos y conteo de productos
  - `/api/v1/categories/{id}` - Ver categoría con productos

- **Protegidas (Super Admin):**
  - `POST /api/v1/products` - Crear producto con imagen
  - `PUT /api/v1/products/{id}` - Actualizar producto con imagen
  - `DELETE /api/v1/products/{id}` - Soft delete
  - `DELETE /api/v1/products/{id}/force` - Eliminación permanente
  - `POST /api/v1/products/{id}/restore` - Restaurar producto
  - `POST /api/v1/products/{id}/stock` - Ajustar stock
  - `POST /api/v1/categories` - Crear categoría
  - `PUT /api/v1/categories/{id}` - Actualizar categoría
  - `PUT /api/v1/categories/reorder` - Reordenar categorías
  - `DELETE /api/v1/categories/{id}` - Soft delete con reasignación de productos
  - `DELETE /api/v1/categories/{id}/force` - Eliminación permanente con reasignación
  - `POST /api/v1/categories/{id}/restore` - Restaurar categoría

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
│   │   │           └── ProductController.php
│   │   ├── Requests/
│   │   │   └── v1/
│   │   │       ├── StoreCategoryRequest.php
│   │   │       ├── UpdateCategoryRequest.php
│   │   │       ├── StoreProductRequest.php
│   │   │       └── UpdateProductRequest.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php (con HasRoles trait)
│   │   ├── Category.php
│   │   ├── Product.php
│   │   └── StockMovement.php
│   └── Services/
│       └── ProductImageService.php
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
│   │   └── 2025_11_07_035051_create_stock_movements_table.php
│   └── seeders/
├── routes/
│   ├── api.php
│   └── v1/
│       ├── auth.php
│       ├── categories.php
│       └── products.php
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
| **Cliente** | Usuario final que compra | Automática en registro |

### Permisos por Módulo

| Acción | Super Admin | Cliente |
|--------|-------------|---------|
| Ver productos | ✅ | ✅ |
| Crear/Editar/Eliminar productos | ✅ | ❌ |
| Ver todas las categorías | ✅ | ✅ |
| Crear/Editar/Eliminar categorías | ✅ | ❌ |
| Gestionar stock | ✅ | ❌ |

---

## 🗄️ ESQUEMA DE BASE DE DATOS ACTUAL

### Tabla: `users`
```sql
- id (bigint, PK)
- name (string)
- email (string, unique)
- password (string)
- email_verified_at (timestamp, nullable)
- created_at, updated_at

NOTA: El campo 'phone' se implementará en la tabla 'addresses' en Fase 5
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
- is_protected (boolean, default false) - Marca categoría "Otros" como no eliminable
- is_active (boolean, default true)
- deleted_at (timestamp, nullable) - Soft deletes
- created_at, updated_at

ÍNDICES:
- index(['level', 'order']) - Para ordenamiento jerárquico eficiente
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
- deleted_at (timestamp, nullable) - Soft deletes
- created_at, updated_at
```

### Tabla: `stock_movements`
```sql
- id (bigint, PK)
- product_id (bigint, FK → products.id, cascade on delete)
- user_id (bigint, FK → users.id, nullable)
- type (enum: entrada, salida, ajuste)
- quantity (integer)
- stock_before (integer)
- stock_after (integer)
- reason (string, nullable)
- created_at, updated_at
```

---

## 🛠️ FUNCIONALIDADES IMPLEMENTADAS

### Sistema de Autenticación (Sanctum)
- Registro de usuarios con asignación automática de rol Cliente
- Login con generación de token
- Logout con revocación de token
- Middleware `auth:sanctum` para rutas protegidas
- Middleware `role:Super Admin` para rutas administrativas

### Gestión de Categorías
- CRUD completo con validaciones avanzadas
- Sistema jerárquico de 4 niveles (0-3)
- Categoría protegida "Otros" (is_protected = true)
- Estrategia de reasignación automática de productos al eliminar
- Validaciones:
  - Prevenir eliminación de categorías protegidas
  - Prevenir modificación de categorías protegidas
  - Prevenir referencias circulares (categoría no puede ser su propio ancestro)
  - Validar que parent tenga level menor
- Soft deletes con posibilidad de restaurar
- Reordenamiento de categorías
- Conteo de productos por categoría

### Gestión de Productos
- CRUD completo con validaciones
- Sistema de imágenes con `ProductImageService`:
  - Subida de imágenes al crear producto
  - Actualización de imágenes (elimina anterior automáticamente)
  - Eliminación de imágenes al borrar producto permanentemente
  - Conversión automática de `is_featured` (acepta 0/1/true/false desde form-data)
- Filtros avanzados: categoría, marca, estado, precio, búsqueda
- Productos destacados
- Soft deletes con posibilidad de restaurar
- Sistema de stock con historial de movimientos

---

## 🚧 SIGUIENTE FASE: FASE 4 - SISTEMA DE PEDIDOS

### Objetivo
Implementar funcionalidad crítica del e-commerce: gestión completa de pedidos.

### Subtareas pendientes:

1. **Migraciones de `orders` y `order_items`**
   - Tabla `orders`: id, user_id, status, total, subtotal, tax, shipping_cost, shipping_address, payment_method, payment_status, notes, etc.
   - Tabla `order_items`: id, order_id, product_id, quantity, price, subtotal
   - Relaciones: orders → user, order_items → order/product

2. **Modelo Order con relaciones**
   - belongsTo User
   - hasMany OrderItem
   - Métodos: calculateTotal(), updateStatus(), canBeCancelled()
   - Scopes: byStatus(), recent(), byUser()

3. **Modelo OrderItem**
   - belongsTo Order
   - belongsTo Product
   - Métodos: calculateSubtotal()

4. **OrderController con lógica de negocio**
   - **Políticas de acceso por rol:**
     - Clientes solo pueden ver sus propios pedidos
     - Super Admin puede ver y gestionar todos los pedidos
   - **Validación de stock al crear pedido:**
     - Verificar disponibilidad de cada producto
     - Reservar stock temporalmente
     - Actualizar stock al confirmar pedido
   - **Actualización automática de stock:**
     - Crear movimiento de stock tipo "salida" por cada producto
     - Actualizar campo stock en tabla products
   - **Estados del pedido:**
     - pending, processing, shipped, delivered, cancelled

5. **Form Requests:**
   - StoreOrderRequest: validar productos, cantidades, dirección de envío
   - UpdateOrderStatusRequest: validar transiciones de estado válidas

6. **Endpoints a crear:**
   - `POST /api/v1/orders` - Crear pedido (Cliente y Super Admin)
     - Validar stock disponible
     - Calcular totales
     - Crear order_items
     - Registrar movimientos de stock
   - `GET /api/v1/orders` - Listar pedidos (filtrado por rol)
     - Cliente: solo sus pedidos
     - Super Admin: todos los pedidos con filtros
   - `GET /api/v1/orders/{id}` - Ver pedido específico
     - Validar que el usuario tenga permiso
   - `PATCH /api/v1/orders/{id}/status` - Actualizar estado (Solo Super Admin)
     - Validar transiciones válidas
   - `DELETE /api/v1/orders/{id}` - Cancelar pedido
     - Solo si está en estado "pending" o "processing"
     - Devolver stock a inventario

7. **Validaciones de negocio:**
   - Stock suficiente para todos los productos
   - Productos activos y disponibles
   - Cálculo correcto de totales
   - Prevenir pedidos con carrito vacío

8. **Integración con frontend (OrderContext)** - PENDIENTE (frontend no integrado)

---

## 📝 NOTAS TÉCNICAS IMPORTANTES

### Form-Data con Laravel
- Laravel no soporta directamente `multipart/form-data` con PUT/PATCH
- **Solución implementada:** Usar POST con `_method=PUT` (method spoofing)
- El método `prepareForValidation()` se ejecuta ANTES de `rules()`
- Conversión de booleanos necesaria para datos desde form-data

### Sistema de Archivos
- Disco `products` apunta a `storage/app/public/products`
- Enlace simbólico creado: `public/storage` → `storage/app/public`
- URL pública: `http://localhost:8000/storage/products/{filename}`

### Validaciones Personalizadas
- **StoreCategoryRequest:** Valida que parent tenga level menor
- **UpdateCategoryRequest:**
  - Previene modificar categorías protegidas
  - Valida nivel del parent
  - Previene referencias circulares con algoritmo de detección
- **StoreProductRequest y UpdateProductRequest:**
  - Método `prepareForValidation()` convierte is_featured a boolean

### Categoría Protegida "Otros"
- **Propósito:** Garantizar que siempre exista al menos una categoría
- **Campo:** `is_protected = true`
- **Restricciones:**
  - No se puede eliminar (HTTP 403)
  - No se puede modificar (HTTP 403)
  - Recibe productos de categorías eliminadas automáticamente
- **Validaciones en CategoryController:**
  - `destroy()`: Verifica is_protected antes de eliminar
  - `forceDelete()`: Verifica is_protected antes de eliminar permanentemente
  - Ambos métodos validan existencia de categoría protegida (HTTP 500 si falta)

---

## 🔧 COMANDOS ÚTILES

```bash
# Ejecutar migraciones
php artisan migrate

# Crear migración
php artisan make:migration create_orders_table

# Crear modelo con migración y controlador
php artisan make:model Order -mc

# Crear Form Request
php artisan make:request StoreOrderRequest

# Crear enlace simbólico de storage
php artisan storage:link

# Ejecutar seeders
php artisan db:seed

# Ver rutas
php artisan route:list

# Rollback última migración
php artisan migrate:rollback

# Refrescar todas las migraciones
php artisan migrate:fresh
```

---

## 📊 ENDPOINTS DISPONIBLES ACTUALMENTE

### Autenticación (Públicos)
- `POST /api/v1/register` - Registro de usuario
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

---

## 🎯 PRÓXIMOS PASOS (FASE 4)

1. Crear migración `orders` con campos:
   - user_id, status (enum), total, subtotal, tax, shipping_cost
   - shipping_address (text o JSON), payment_method, payment_status
   - notes, tracking_number, etc.

2. Crear migración `order_items` con campos:
   - order_id, product_id, quantity, price, subtotal

3. Crear modelo `Order` con:
   - Relaciones: belongsTo User, hasMany OrderItem
   - Métodos: calculateTotal(), updateStatus(), canBeCancelled()
   - Scopes: byStatus(), recent(), byUser()
   - Mutators/Accessors según necesidad

4. Crear modelo `OrderItem` con:
   - Relaciones: belongsTo Order, belongsTo Product
   - Método: calculateSubtotal()

5. Implementar `OrderController` con:
   - Política de acceso por rol
   - Validación de stock
   - Creación de pedidos con transacciones
   - Actualización de stock automática
   - Gestión de estados

6. Crear Form Requests:
   - StoreOrderRequest
   - UpdateOrderStatusRequest

7. Crear rutas en `routes/v1/orders.php`

8. Probar endpoints en Postman

---

## 🔍 REFERENCIAS

- **Plan original:** `docs/Plan-Context.md`
- **Documentación Laravel 12:** https://laravel.com/docs/12.x
- **Spatie Permissions:** https://spatie.be/docs/laravel-permission/v6
- **Laravel Sanctum:** https://laravel.com/docs/12.x/sanctum

---

**Documento creado:** 2025-11-09
**Mantenido por:** Claude
**Versión:** 1.0
