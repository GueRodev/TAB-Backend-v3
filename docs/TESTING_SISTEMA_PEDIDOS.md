# Guía de Testing - Sistema de Pedidos (Fase 4)

## Información de las Rutas

**Archivos de rutas:**
- Cliente: `routes/v1/orders.php`
- Admin: `routes/v1/admin_orders.php`

**Prefijos de endpoints:**
- Cliente: `/api/v1/orders`
- Admin: `/api/v1/admin/orders`

---

## Tabla de Contenidos
1. [Preparación del Entorno](#preparación-del-entorno)
2. [Testing de Rutas de Cliente](#testing-de-rutas-de-cliente)
3. [Testing de Rutas de Admin](#testing-de-rutas-de-admin)
4. [Testing del Sistema de Stock](#testing-del-sistema-de-stock)
5. [Testing del Sistema de Emails](#testing-del-sistema-de-emails)
6. [Casos de Prueba Completos](#casos-de-prueba-completos)

---

## Preparación del Entorno

### 1. Ejecutar Migraciones
```bash
cd Backend-Api-TAB-v3
php artisan migrate
```

### 2. Verificar Configuración de Email
Editar `.env`:
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@toysandbricks.com
MAIL_FROM_NAME="Toys and Bricks"
```

### 3. Poblar Base de Datos (Opcional)
```bash
# Ejecutar seeder de pedidos
php artisan db:seed --class=OrderSeeder
```

### 4. Obtener Token de Autenticación
Necesitarás tokens para:
- **Cliente**: Usuario con rol "Cliente"
- **Super Admin**: Usuario con rol "Super Admin"

**Login Request (Postman)**
```
POST http://localhost:8000/api/v1/auth/login
Content-Type: application/json

{
    "email": "cliente@example.com",
    "password": "password"
}
```

Guardar el `token` de la respuesta.

---

## Testing de Rutas de Cliente

**Archivo de rutas:** `routes/v1/orders.php`

### 1. Listar Pedidos del Cliente
**Endpoint:** `GET /api/v1/orders`
**Auth:** Bearer Token (Cliente)

**Request:**
```
GET http://localhost:8000/api/v1/orders
Authorization: Bearer {TOKEN_CLIENTE}
```

**Query Parameters (Opcionales):**
- `status`: pending, in_progress, completed, cancelled, archived
- `order_type`: online, in_store
- `per_page`: número de resultados por página (default: 15)

**Ejemplos:**
```
GET http://localhost:8000/api/v1/orders?status=completed
GET http://localhost:8000/api/v1/orders?order_type=online&per_page=10
```

**Respuesta Esperada (200):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "order_number": "ORD-20251111-0001",
                "customer_name": "Juan Pérez",
                "customer_email": "juan@example.com",
                "status": "completed",
                "order_type": "online",
                "delivery_option": "delivery",
                "payment_method": "card",
                "subtotal": "50000.00",
                "shipping_cost": "2500.00",
                "total": "52500.00",
                "created_at": "2025-11-11T10:00:00.000000Z",
                "items": [...],
                "shipping_address": {...}
            }
        ],
        "total": 5,
        "per_page": 15
    }
}
```

---

### 2. Crear Pedido Online
**Endpoint:** `POST /api/v1/orders`
**Auth:** Bearer Token (Cliente)

**Request Body (Envío a Domicilio):**
```json
{
    "customer_name": "María González",
    "customer_email": "maria@example.com",
    "customer_phone": "88887777",
    "delivery_option": "delivery",
    "payment_method": "sinpe",
    "notes": "Entregar en horario de oficina",
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        },
        {
            "product_id": 3,
            "quantity": 1
        }
    ],
    "shipping_address": {
        "province": "San José",
        "canton": "Escazú",
        "district": "San Rafael",
        "address_details": "100m norte de la iglesia, casa amarilla con portón negro"
    }
}
```

**Request Body (Recoger en Tienda):**
```json
{
    "customer_name": "Carlos Ramírez",
    "customer_email": "carlos@example.com",
    "customer_phone": "77776666",
    "delivery_option": "pickup",
    "payment_method": "cash",
    "items": [
        {
            "product_id": 2,
            "quantity": 1
        }
    ]
}
```

**Respuesta Esperada (201):**
```json
{
    "success": true,
    "message": "Pedido creado exitosamente",
    "data": {
        "id": 15,
        "order_number": "ORD-20251111-0015",
        "customer_name": "María González",
        "status": "pending",
        "order_type": "online",
        "total": "52500.00",
        "items": [...]
    }
}
```

**Casos de Error a Probar:**
- Sin items: `422 - The items field is required`
- Producto sin stock: `422 - Stock insuficiente para...`
- delivery_option=delivery sin shipping_address: `422 - The shipping address field is required`
- quantity menor a 1: `422 - The items.0.quantity field must be at least 1`

---

### 3. Ver Detalles de un Pedido
**Endpoint:** `GET /api/v1/orders/{id}`
**Auth:** Bearer Token (Cliente)

**Request:**
```
GET http://localhost:8000/api/v1/orders/15
Authorization: Bearer {TOKEN_CLIENTE}
```

**Respuesta Esperada (200):**
```json
{
    "success": true,
    "data": {
        "id": 15,
        "order_number": "ORD-20251111-0015",
        "customer_name": "María González",
        "customer_email": "maria@example.com",
        "customer_phone": "88887777",
        "status": "pending",
        "order_type": "online",
        "delivery_option": "delivery",
        "payment_method": "sinpe",
        "subtotal": "50000.00",
        "shipping_cost": "2500.00",
        "total": "52500.00",
        "notes": "Entregar en horario de oficina",
        "items": [
            {
                "id": 28,
                "product_id": 1,
                "product_name": "LEGO Star Wars",
                "product_sku": "LEGO-SW-001",
                "quantity": 2,
                "price_at_purchase": "15000.00",
                "subtotal": "30000.00"
            }
        ],
        "shipping_address": {
            "id": 8,
            "province": "San José",
            "canton": "Escazú",
            "district": "San Rafael",
            "address_details": "100m norte de la iglesia...",
            "full_address": "San José, Escazú, San Rafael - 100m norte de la iglesia..."
        },
        "stock_movements": [
            {
                "id": 42,
                "product_id": 1,
                "type": "reserva",
                "quantity": -2,
                "reason": "Reserva para pedido ORD-20251111-0015"
            }
        ]
    }
}
```

**Casos de Error a Probar:**
- Pedido inexistente: `404 - No query results for model`
- Intentar ver pedido de otro cliente: `403 - No autorizado`

---

## Testing de Rutas de Admin

**Archivo de rutas:** `routes/v1/admin_orders.php`

### 1. Listar Todos los Pedidos (Admin)
**Endpoint:** `GET /api/v1/admin/orders`
**Auth:** Bearer Token (Super Admin)

**Request:**
```
GET http://localhost:8000/api/v1/admin/orders
Authorization: Bearer {TOKEN_ADMIN}
```

**Query Parameters Disponibles:**
- `status`: pending, in_progress, completed, cancelled, archived
- `order_type`: online, in_store
- `delivery_option`: pickup, delivery
- `payment_method`: cash, card, transfer, sinpe
- `customer_email`: email del cliente
- `order_number`: número de pedido específico
- `per_page`: resultados por página (default: 15)

**Ejemplos:**
```
GET http://localhost:8000/api/v1/admin/orders?status=pending&order_type=online
GET http://localhost:8000/api/v1/admin/orders?customer_email=maria@example.com
GET http://localhost:8000/api/v1/admin/orders?order_number=ORD-20251111-0015
```

---

### 2. Crear Pedido en Tienda Física (Admin)
**Endpoint:** `POST /api/v1/admin/orders`
**Auth:** Bearer Token (Super Admin)

**Request Body:**
```json
{
    "customer_name": "Luis Fernández",
    "customer_phone": "66665555",
    "payment_method": "card",
    "notes": "Cliente prefiere bolsa de regalo",
    "items": [
        {
            "product_id": 5,
            "quantity": 1
        },
        {
            "product_id": 8,
            "quantity": 3
        }
    ]
}
```

**Características del Pedido In-Store:**
- `order_type`: siempre "in_store"
- `delivery_option`: siempre "pickup"
- `customer_email`: opcional
- `shipping_cost`: siempre 0

**Respuesta Esperada (201):**
```json
{
    "success": true,
    "message": "Pedido en tienda creado exitosamente",
    "data": {
        "id": 16,
        "order_number": "ORD-20251111-0016",
        "customer_name": "Luis Fernández",
        "status": "pending",
        "order_type": "in_store",
        "delivery_option": "pickup",
        "total": "35000.00"
    }
}
```

---

### 3. Ver Detalles de Cualquier Pedido (Admin)
**Endpoint:** `GET /api/v1/admin/orders/{id}`
**Auth:** Bearer Token (Super Admin)

**Request:**
```
GET http://localhost:8000/api/v1/admin/orders/15
Authorization: Bearer {TOKEN_ADMIN}
```

**Diferencia con ruta de cliente:**
- Admin puede ver pedidos de cualquier usuario
- Incluye relación `user` (información del cliente autenticado si existe)

---

### 4. Marcar Pedido como En Progreso
**Endpoint:** `PATCH /api/v1/admin/orders/{id}/mark-in-progress`
**Auth:** Bearer Token (Super Admin)

**Request:**
```
PATCH http://localhost:8000/api/v1/admin/orders/15/mark-in-progress
Authorization: Bearer {TOKEN_ADMIN}
```

**Respuesta Esperada (200):**
```json
{
    "success": true,
    "message": "Pedido marcado como en progreso",
    "data": {
        "id": 15,
        "order_number": "ORD-20251111-0015",
        "status": "in_progress",
        "updated_at": "2025-11-11T11:30:00.000000Z"
    }
}
```

**Transiciones Válidas:**
- `pending` → `in_progress` ✅

**Casos de Error:**
- Estado actual no es 'pending': `422 - Solo los pedidos pendientes pueden marcarse como en proceso`

---

### 5. Completar Pedido
**Endpoint:** `PATCH /api/v1/admin/orders/{id}/complete`
**Auth:** Bearer Token (Super Admin)

**Request:**
```
PATCH http://localhost:8000/api/v1/admin/orders/15/complete
Authorization: Bearer {TOKEN_ADMIN}
```

**Acciones Automáticas al Completar:**
1. ✅ Confirma la venta (convierte reserva → venta)
2. ✅ Descuenta el stock físico de productos
3. ✅ Envía email de comprobante al cliente
4. ✅ Actualiza estado a 'completed'

**Respuesta Esperada (200):**
```json
{
    "success": true,
    "message": "Pedido completado y email enviado",
    "data": {
        "id": 15,
        "order_number": "ORD-20251111-0015",
        "status": "completed",
        "updated_at": "2025-11-11T12:00:00.000000Z"
    }
}
```

**Transiciones Válidas:**
- `pending` → `completed` ✅
- `in_progress` → `completed` ✅

**Casos de Error:**
- Estado actual es 'completed': `422 - El pedido ya está completado`
- Estado actual es 'cancelled': `422 - No se puede completar un pedido cancelado`
- Stock insuficiente: `422 - Stock insuficiente para confirmar el pedido`

---

### 6. Cancelar Pedido
**Endpoint:** `PATCH /api/v1/admin/orders/{id}/cancel`
**Auth:** Bearer Token (Super Admin)

**Request:**
```
PATCH http://localhost:8000/api/v1/admin/orders/15/cancel
Authorization: Bearer {TOKEN_ADMIN}
```

**Acciones Automáticas al Cancelar:**
1. ✅ Libera el stock reservado (crea movimiento 'cancelacion_reserva')
2. ✅ Actualiza estado a 'cancelled'

**Respuesta Esperada (200):**
```json
{
    "success": true,
    "message": "Pedido cancelado y stock liberado",
    "data": {
        "id": 15,
        "order_number": "ORD-20251111-0015",
        "status": "cancelled",
        "updated_at": "2025-11-11T12:15:00.000000Z"
    }
}
```

**Transiciones Válidas:**
- `pending` → `cancelled` ✅
- `in_progress` → `cancelled` ✅

**Casos de Error:**
- Estado actual es 'completed': `422 - No se puede cancelar un pedido completado`
- Estado actual es 'archived': `422 - No se puede cancelar un pedido archivado`

---

### 7. Archivar Pedido
**Endpoint:** `POST /api/v1/admin/orders/{id}/archive`
**Auth:** Bearer Token (Super Admin)

**Request:**
```
POST http://localhost:8000/api/v1/admin/orders/15/archive
Authorization: Bearer {TOKEN_ADMIN}
```

**Propósito:** Archivar pedidos completados antiguos para mantener limpia la lista activa.

**Respuesta Esperada (200):**
```json
{
    "success": true,
    "message": "Pedido archivado exitosamente",
    "data": {
        "id": 15,
        "order_number": "ORD-20251111-0015",
        "status": "archived",
        "updated_at": "2025-11-11T12:30:00.000000Z"
    }
}
```

**Transiciones Válidas:**
- `completed` → `archived` ✅
- `cancelled` → `archived` ✅

**Casos de Error:**
- Estado actual es 'pending': `422 - Solo los pedidos completados o cancelados pueden archivarse`
- Estado actual es 'in_progress': `422 - Solo los pedidos completados o cancelados pueden archivarse`

---

### 8. Eliminar Pedido (Soft Delete)
**Endpoint:** `DELETE /api/v1/admin/orders/{id}`
**Auth:** Bearer Token (Super Admin)

**Request:**
```
DELETE http://localhost:8000/api/v1/admin/orders/15
Authorization: Bearer {TOKEN_ADMIN}
```

**Acciones Automáticas:**
1. ✅ Soft delete del pedido (mantiene registro en BD)
2. ✅ Si está en 'pending' o 'in_progress', libera stock automáticamente

**Respuesta Esperada (200):**
```json
{
    "success": true,
    "message": "Pedido eliminado exitosamente"
}
```

**Nota:** El pedido sigue en la base de datos con `deleted_at` timestamp pero no aparece en consultas normales.

---

## Testing del Sistema de Stock

### Escenario 1: Verificar Reserva de Stock
1. **Ver stock actual de un producto:**
```sql
SELECT id, name, stock FROM products WHERE id = 1;
```

2. **Crear pedido con 2 unidades del producto:**
```
POST /api/v1/orders
{
    "items": [{"product_id": 1, "quantity": 2}],
    ...
}
```

3. **Verificar movimientos de stock:**
```sql
SELECT * FROM stock_movements WHERE product_id = 1 ORDER BY id DESC LIMIT 5;
```

Debe mostrar:
- `type: 'reserva'`
- `quantity: -2`
- `order_id: {id del pedido}`
- `reason: 'Reserva para pedido ORD-...'`

4. **Verificar que el stock físico NO cambió:**
```sql
SELECT stock FROM products WHERE id = 1;
```
El stock físico debe ser el mismo que en el paso 1.

---

### Escenario 2: Verificar Confirmación de Venta
1. **Completar el pedido:**
```
PATCH /api/v1/admin/orders/{id}/complete
```

2. **Verificar nuevos movimientos:**
```sql
SELECT * FROM stock_movements WHERE order_id = {id} ORDER BY id DESC;
```

Debe mostrar DOS movimientos:
- `type: 'reserva'` (del paso anterior)
- `type: 'venta'` (nuevo)
  - `quantity: -2`
  - `reason: 'Venta confirmada para pedido ORD-...'`

3. **Verificar que el stock físico SÍ disminuyó:**
```sql
SELECT stock FROM products WHERE id = 1;
```
El stock físico debe haber disminuido en 2 unidades.

---

### Escenario 3: Verificar Cancelación
1. **Crear un pedido:**
```
POST /api/v1/orders
{
    "items": [{"product_id": 2, "quantity": 3}],
    ...
}
```

2. **Verificar reserva:**
```sql
SELECT * FROM stock_movements WHERE product_id = 2 ORDER BY id DESC LIMIT 1;
```
Debe mostrar `type: 'reserva'`

3. **Cancelar el pedido:**
```
PATCH /api/v1/admin/orders/{id}/cancel
```

4. **Verificar cancelación de reserva:**
```sql
SELECT * FROM stock_movements WHERE order_id = {id} ORDER BY id DESC;
```

Debe mostrar DOS movimientos:
- `type: 'reserva'` (quantity: -3)
- `type: 'cancelacion_reserva'` (quantity: +3)

5. **Verificar que el stock físico NO cambió:**
```sql
SELECT stock FROM products WHERE id = 2;
```
El stock físico debe ser el mismo que antes de crear el pedido.

---

### Escenario 4: Verificar Stock Insuficiente
1. **Ver stock disponible:**
```sql
SELECT p.id, p.name, p.stock,
       COALESCE(SUM(CASE WHEN sm.type = 'reserva' THEN sm.quantity ELSE 0 END), 0) as reserved
FROM products p
LEFT JOIN stock_movements sm ON p.id = sm.product_id
WHERE p.id = 3
GROUP BY p.id;
```

2. **Intentar crear pedido que exceda el stock:**
```
POST /api/v1/orders
{
    "items": [{"product_id": 3, "quantity": 9999}],
    ...
}
```

**Respuesta Esperada (422):**
```json
{
    "success": false,
    "message": "Error al crear el pedido",
    "error": "Stock insuficiente para el producto: {nombre}. Stock disponible: {cantidad}"
}
```

---

## Testing del Sistema de Emails

### Verificar Email en Log
Cuando completes un pedido, el email se guardará en:
```
Backend-Api-TAB-v3/storage/logs/laravel.log
```

Busca en el log:
```
Subject: Comprobante de Pedido - ORD-XXXXXXXX-XXXX
```

### Contenido del Email
El email debe incluir:
- ✅ Saludo con nombre del cliente
- ✅ Número de pedido
- ✅ Fecha del pedido
- ✅ Estado (Completado)
- ✅ Tipo de pedido (Online/En Tienda)
- ✅ Método de pago
- ✅ Tipo de entrega (Pickup/Delivery)
- ✅ Dirección de envío (si aplica)
- ✅ Tabla de productos con:
  - Nombre del producto
  - SKU
  - Cantidad
  - Precio unitario
  - Subtotal
- ✅ Subtotal del pedido
- ✅ Costo de envío (si aplica)
- ✅ Total con símbolo ₡ (colones)
- ✅ Notas (si existen)
- ✅ Información de contacto

### Testing Manual del Email
Para ver el email renderizado en el navegador:

1. **Crear ruta temporal en** `routes/web.php`:
```php
use App\Models\Order;
use App\Mail\OrderReceiptMail;

Route::get('/test-email/{orderId}', function ($orderId) {
    $order = Order::with(['items', 'shippingAddress'])->findOrFail($orderId);
    return new OrderReceiptMail($order);
});
```

2. **Visitar en el navegador:**
```
http://localhost:8000/test-email/15
```

3. **Eliminar la ruta temporal después de verificar.**

---

## Casos de Prueba Completos

### Flujo 1: Pedido Online Exitoso (Delivery)
1. ✅ Cliente crea pedido online con envío a domicilio
2. ✅ Verificar que stock se reserva (no se descuenta)
3. ✅ Admin marca pedido como 'in_progress'
4. ✅ Admin completa pedido
5. ✅ Verificar que stock se descuenta físicamente
6. ✅ Verificar que email se envía
7. ✅ Cliente consulta su pedido y ve estado 'completed'
8. ✅ Admin archiva el pedido

**SQL para verificar:**
```sql
-- Ver el pedido completo
SELECT * FROM orders WHERE id = {id};

-- Ver items del pedido
SELECT * FROM order_items WHERE order_id = {id};

-- Ver dirección de envío
SELECT * FROM order_shipping_addresses WHERE order_id = {id};

-- Ver movimientos de stock
SELECT * FROM stock_movements WHERE order_id = {id};

-- Ver stock actual de productos
SELECT p.id, p.name, p.stock
FROM products p
JOIN order_items oi ON p.id = oi.product_id
WHERE oi.order_id = {id};
```

---

### Flujo 2: Pedido Online Cancelado
1. ✅ Cliente crea pedido online
2. ✅ Verificar que stock se reserva
3. ✅ Admin marca como 'in_progress'
4. ✅ Cliente llama para cancelar
5. ✅ Admin cancela el pedido
6. ✅ Verificar que stock se libera (movimiento 'cancelacion_reserva')
7. ✅ Verificar que stock físico no cambió

---

### Flujo 3: Pedido en Tienda Física (Pickup)
1. ✅ Admin crea pedido desde la tienda
2. ✅ Verificar que order_type es 'in_store'
3. ✅ Verificar que delivery_option es 'pickup'
4. ✅ Verificar que shipping_cost es 0
5. ✅ Admin completa pedido inmediatamente
6. ✅ Verificar que stock se descuenta
7. ✅ Email se envía (si se proporcionó email)

---

### Flujo 4: Validaciones de Autorización
1. ✅ Cliente NO puede ver pedidos de otro cliente
2. ✅ Cliente NO puede acceder a rutas /admin/orders
3. ✅ Cliente NO puede crear pedidos in_store
4. ✅ Cliente NO puede cambiar estados de pedidos
5. ✅ Usuario sin token recibe 401 Unauthenticated
6. ✅ Usuario autenticado sin rol Admin recibe 403 en rutas admin

---

### Flujo 5: Validaciones de Stock
1. ✅ No se puede crear pedido con producto sin stock
2. ✅ No se puede completar pedido si stock insuficiente (otro pedido lo consumió)
3. ✅ Stock reservado no está disponible para otros pedidos
4. ✅ Stock cancelado vuelve a estar disponible

---

## Checklist Final de Testing

### Rutas de Cliente (/api/v1/orders)
- [ ] GET /orders - Lista solo pedidos del cliente autenticado
- [ ] GET /orders?status=completed - Filtro por estado funciona
- [ ] POST /orders (delivery) - Crea pedido con envío correctamente
- [ ] POST /orders (pickup) - Crea pedido para recoger correctamente
- [ ] POST /orders sin items - Retorna error 422
- [ ] POST /orders con producto sin stock - Retorna error 422
- [ ] POST /orders (delivery) sin address - Retorna error 422
- [ ] GET /orders/{id} - Muestra detalles del pedido propio
- [ ] GET /orders/{id} de otro cliente - Retorna error 403

### Rutas de Admin (/api/v1/admin/orders)
- [ ] GET /admin/orders - Lista todos los pedidos
- [ ] GET /admin/orders?order_type=online - Filtro funciona
- [ ] GET /admin/orders?customer_email=X - Filtro funciona
- [ ] GET /admin/orders?order_number=X - Filtro funciona
- [ ] POST /admin/orders - Crea pedido en tienda
- [ ] GET /admin/orders/{id} - Ver cualquier pedido
- [ ] PATCH /admin/orders/{id}/mark-in-progress - Funciona
- [ ] PATCH /admin/orders/{id}/complete - Completa y envía email
- [ ] PATCH /admin/orders/{id}/cancel - Cancela y libera stock
- [ ] POST /admin/orders/{id}/archive - Archiva pedido
- [ ] DELETE /admin/orders/{id} - Elimina (soft delete)

### Sistema de Stock
- [ ] Crear pedido reserva stock (no descuenta)
- [ ] Completar pedido descuenta stock físico
- [ ] Cancelar pedido libera stock reservado
- [ ] Stock insuficiente impide crear pedido
- [ ] Stock insuficiente impide completar pedido
- [ ] Movimientos de stock se registran correctamente

### Sistema de Emails
- [ ] Email se envía al completar pedido
- [ ] Email contiene información correcta
- [ ] Email usa formato de colones (₡)
- [ ] Email incluye dirección si es delivery
- [ ] Email incluye notas si existen
- [ ] Fallo de email no impide completar pedido

### Transiciones de Estado
- [ ] pending → in_progress funciona
- [ ] pending → completed funciona
- [ ] pending → cancelled funciona
- [ ] in_progress → completed funciona
- [ ] in_progress → cancelled funciona
- [ ] completed → archived funciona
- [ ] cancelled → archived funciona
- [ ] completed → cancelled NO funciona (error)
- [ ] archived → cualquier otro NO funciona (error)

### Autenticación y Autorización
- [ ] Sin token: 401 Unauthenticated
- [ ] Cliente en ruta admin: 403 Forbidden
- [ ] Cliente ve solo sus pedidos
- [ ] Admin ve todos los pedidos

---

## Herramientas Recomendadas

### Postman Collection
Considera crear una colección de Postman con:
- Variables de entorno (BASE_URL, TOKEN_CLIENTE, TOKEN_ADMIN)
- Todos los endpoints documentados arriba
- Tests automatizados para validar respuestas

### SQL Queries Útiles
```sql
-- Resumen de pedidos por estado
SELECT status, COUNT(*) as total, SUM(total) as monto_total
FROM orders
GROUP BY status;

-- Productos más vendidos
SELECT p.name, SUM(oi.quantity) as cantidad_vendida, SUM(oi.subtotal) as ventas
FROM order_items oi
JOIN products p ON oi.product_id = p.id
JOIN orders o ON oi.order_id = o.id
WHERE o.status = 'completed'
GROUP BY p.id, p.name
ORDER BY cantidad_vendida DESC;

-- Movimientos de stock de un producto
SELECT sm.*, o.order_number
FROM stock_movements sm
LEFT JOIN orders o ON sm.order_id = o.id
WHERE sm.product_id = {id}
ORDER BY sm.created_at DESC;

-- Pedidos pendientes de procesar
SELECT * FROM orders
WHERE status IN ('pending', 'in_progress')
ORDER BY created_at ASC;
```

---

## Notas Finales

- Todos los endpoints retornan JSON
- Los timestamps están en formato ISO 8601
- Los montos están en formato decimal con 2 decimales
- El sistema usa soft deletes (registros nunca se eliminan permanentemente)
- Los movimientos de stock son inmutables (audit trail completo)
- Los snapshots de productos en order_items preservan precios históricos
