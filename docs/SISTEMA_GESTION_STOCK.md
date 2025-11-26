# Sistema de Gestión de Stock

Documentación completa del sistema de gestión de inventario y movimientos de stock.

---

## Tabla de Contenidos

1. [Visión General](#visión-general)
2. [Ubicación en el Código](#ubicación-en-el-código)
3. [Tipos de Movimientos](#tipos-de-movimientos)
4. [Diferencias entre Métodos](#diferencias-entre-métodos)
5. [Diferencia: Entrada vs Ajuste](#diferencia-entrada-vs-ajuste)
6. [Flujos de Trabajo](#flujos-de-trabajo)
7. [Modelo de Datos](#modelo-de-datos)
8. [Ejemplos de Uso](#ejemplos-de-uso)
9. [Mejores Prácticas](#mejores-prácticas)

---

## Visión General

El sistema implementa un control completo de inventario con trazabilidad total mediante la tabla `stock_movements`. Cada cambio en el stock queda registrado con:

- **Tipo de movimiento** (entrada, salida, ajuste, reserva, venta, cancelación)
- **Cantidad** del movimiento
- **Stock antes y después** del movimiento
- **Razón** o motivo del cambio
- **Usuario** que realizó la acción
- **Pedido** relacionado (si aplica)
- **Fecha y hora** exacta

---

## Ubicación en el Código

### Archivos Principales

| Archivo | Descripción | Líneas Clave |
|---------|-------------|--------------|
| `app/Http/Controllers/Api/v1/ProductController.php` | Controlador de productos con métodos de ajuste | 108-140, 250-287 |
| `app/Models/StockMovement.php` | Modelo de movimientos de stock | Completo |
| `app/Services/StockReservationService.php` | Servicio para reservas y ventas | Completo |
| `database/migrations/..._create_stock_movements_table.php` | Estructura de la tabla | 14-27 |
| `routes/v1/products.php` | Rutas de productos | 27 |

### Endpoints Principales

```http
# Ajustar stock manualmente
POST /api/v1/products/{id}/stock

# Actualizar producto (puede cambiar stock)
PUT /api/v1/products/{id}

# Ver historial de movimientos
GET /api/v1/products/{id}/stock-movements

# Verificar disponibilidad
POST /api/v1/stock-movements/check-availability
```

---

## Tipos de Movimientos

El sistema soporta **6 tipos** de movimientos de stock:

### Movimientos Manuales (ProductController)

| Tipo | Operación | Fórmula | Uso |
|------|-----------|---------|-----|
| **entrada** | Suma | `stock_nuevo = stock_actual + cantidad` | Compras, devoluciones |
| **salida** | Resta | `stock_nuevo = stock_actual - cantidad` | Daños, muestras, robos |
| **ajuste** | Reemplazo | `stock_nuevo = cantidad` | Inventarios físicos |

### Movimientos Automáticos (StockReservationService)

| Tipo | Operación | Fórmula | Uso |
|------|-----------|---------|-----|
| **reserva** | Reserva virtual | `stock_reservado += cantidad` (stock real NO cambia) | Pedido pendiente |
| **venta** | Descuenta stock | `stock_real -= cantidad` | Pedido confirmado/pagado |
| **cancelacion_reserva** | Libera reserva | `stock_reservado -= cantidad` (stock real NO cambia) | Pedido cancelado |

### Código de Tipos

```php
// En la migración: create_stock_movements_table.php
$table->enum('type', [
    'entrada',              // +stock (manual)
    'salida',               // -stock (manual)
    'ajuste',               // =stock (manual)
    'reserva',              // reserva virtual (auto)
    'venta',                // descuenta real (auto)
    'cancelacion_reserva'   // libera reserva (auto)
]);
```

---

## Diferencias entre Métodos

### 1. Editar Stock Normal (Update Product)

**Endpoint:** `PUT /api/v1/products/{id}`

```php
// ProductController.php líneas 131-134
if ($oldStock != $product->stock) {
    $this->createStockMovement($product, 'ajuste',
        $product->stock - $oldStock, $oldStock, 'Actualización manual');
}
```

**Características:**
- ✅ Forma indirecta (editas el producto completo)
- ✅ Tipo automático: siempre es `ajuste`
- ✅ Razón genérica: "Actualización manual"
- ⚠️ Menos control sobre el tipo de movimiento

**Ejemplo de Uso:**
```http
PUT /api/v1/products/5
Content-Type: application/json

{
  "name": "Producto X",
  "price": 150.00,
  "stock": 50
}

# Movimiento registrado:
# - type: "ajuste"
# - reason: "Actualización manual"
```

---

### 2. Ajuste de Stock Específico

**Endpoint:** `POST /api/v1/products/{id}/stock`

```php
// ProductController.php líneas 250-287
$request->validate([
    'type' => 'required|in:entrada,salida,ajuste',
    'quantity' => 'required|integer|min:1',
    'reason' => 'nullable|string|max:255',
]);
```

**Características:**
- ✅ Forma directa y específica
- ✅ Control total: eliges el tipo (entrada/salida/ajuste)
- ✅ Razón personalizada: documenta el motivo exacto
- ✅ Validaciones específicas (ej: salida verifica stock suficiente)
- ✅ Mejor trazabilidad y auditoría

**Ejemplo de Uso:**
```http
POST /api/v1/products/5/stock
Content-Type: application/json

{
  "type": "entrada",
  "quantity": 50,
  "reason": "Compra proveedor ABC - Factura #12345"
}

# Movimiento registrado:
# - type: "entrada"
# - reason: "Compra proveedor ABC - Factura #12345"
```

---

### Comparativa: ¿Cuál usar?

| Aspecto | Editar Producto (PUT) | Ajustar Stock (POST) |
|---------|----------------------|---------------------|
| **Propósito** | Actualizar producto completo | Solo gestionar stock |
| **Tipo movimiento** | Siempre `ajuste` | `entrada`, `salida`, o `ajuste` |
| **Razón** | Genérica ("Actualización manual") | Personalizada |
| **Validaciones** | Básicas | Específicas por tipo |
| **Trazabilidad** | Baja | Alta |
| **Uso recomendado** | Cambios generales de producto | Gestión específica de inventario |

**Importante:** Ambos métodos conviven perfectamente sin conflictos. Puedes usar uno y luego el otro sin problemas.

---

## Diferencia: Entrada vs Ajuste

Esta es una de las preguntas más frecuentes. La diferencia está en **cómo calculan el nuevo stock**:

### Código de Cálculo

```php
// ProductController.php líneas 271-275
$newStock = match($request->type) {
    'entrada' => $oldStock + $request->quantity,    // ← SUMA
    'salida' => max(0, $oldStock - $request->quantity),  // ← RESTA
    'ajuste' => $request->quantity,  // ← REEMPLAZA
};
```

---

### ENTRADA (Operación Relativa - Suma)

**Fórmula:** `nuevo_stock = stock_actual + cantidad`

**Ejemplo:**
```http
# Stock actual: 100
POST /api/v1/products/1/stock
{
  "type": "entrada",
  "quantity": 50,
  "reason": "Compra proveedor XYZ"
}

# Resultado: 100 + 50 = 150 ✅
```

**Interpretación:** "Sumar 50 unidades al stock actual"

**Cuándo usar:**
- ✅ Llegó mercancía nueva del proveedor
- ✅ Cliente devolvió productos
- ✅ Encontraste unidades extras
- ✅ Producción entregó nuevas unidades

**Dato que ingresas:** Cuántas unidades LLEGARON

---

### AJUSTE (Operación Absoluta - Reemplazo)

**Fórmula:** `nuevo_stock = cantidad`

**Ejemplo:**
```http
# Stock actual: 100
POST /api/v1/products/1/stock
{
  "type": "ajuste",
  "quantity": 50,
  "reason": "Corrección inventario físico"
}

# Resultado: stock = 50 ✅
```

**Interpretación:** "Establecer el stock exactamente a 50 unidades"

**Cuándo usar:**
- ✅ Hiciste inventario físico y contaste todo
- ✅ Hay diferencia entre sistema y realidad
- ✅ Corrección de errores previos
- ✅ Reinicio de stock

**Dato que ingresas:** Cuántas unidades HAY en total

---

### Tabla Comparativa

| Aspecto | ENTRADA | AJUSTE |
|---------|---------|--------|
| **Operación** | Suma (+) | Reemplazo (=) |
| **Fórmula** | `stock + cantidad` | `cantidad` |
| **Dato que ingresas** | Cuánto llegó | Cuánto hay total |
| **Uso típico** | Compras, devoluciones | Inventarios físicos, correcciones |
| **Ejemplo** | "Llegaron 20 unidades" | "Hay 50 unidades en total" |

---

### Ejemplo Comparativo Lado a Lado

**Estado inicial: 100 unidades**

#### Escenario A: Llegó mercancía (20 unidades)

❌ **MAL - Usando ajuste:**
```json
{
  "type": "ajuste",
  "quantity": 20
}
// Resultado: 20 (¡Perdiste 80 unidades!)
```

✅ **BIEN - Usando entrada:**
```json
{
  "type": "entrada",
  "quantity": 20
}
// Resultado: 120 ✅
```

---

#### Escenario B: Inventario físico (contaste 85 unidades)

❌ **MAL - Usando salida:**
```json
{
  "type": "salida",
  "quantity": 15
}
// Resultado: 85, pero NO sabes si fue robo, daño, o error
```

✅ **BIEN - Usando ajuste:**
```json
{
  "type": "ajuste",
  "quantity": 85,
  "reason": "Inventario físico - diferencia de 15 unidades"
}
// Resultado: 85 ✅ y documentas que fue por inventario
```

---

### Regla Nemotécnica Simple

```
📦 ENTRADA = "Me LLEGARON X unidades"
   → Sumo

📊 AJUSTE = "HAY X unidades en total"
   → Reemplazo
```

---

### Error Común a Evitar

```javascript
// Stock actual: 100
// Hiciste inventario y contaste 130 unidades

// ❌ ERROR COMÚN - Usando entrada
{
  "type": "entrada",
  "quantity": 130
}
// Resultado: 100 + 130 = 230 (¡INCORRECTO!)

// ✅ CORRECTO - Usando ajuste
{
  "type": "ajuste",
  "quantity": 130
}
// Resultado: 130 ✅
```

---

## Flujos de Trabajo

### Flujo 1: Entrada/Salida/Ajuste Manual

```
Usuario (Super Admin)
    ↓
POST /api/v1/products/{id}/stock
{
  "type": "entrada",
  "quantity": 50,
  "reason": "Compra proveedor X"
}
    ↓
ProductController::adjustStock()
    ↓
Validar tipo y cantidad
    ↓
Calcular nuevo stock según tipo
    ↓
Actualizar product.stock
    ↓
Crear registro en stock_movements
(con user_id, reason, stock_before, stock_after)
    ↓
Retornar producto actualizado
```

---

### Flujo 2: Reserva/Venta (Automático con Pedidos)

```
Cliente crea pedido
    ↓
OrderController → StockReservationService::reserveStock()
    ↓
Verificar disponibilidad (stock real - stock reservado)
    ↓
Crear movimientos tipo "reserva"
(stock real NO cambia, solo se marca como reservado)
    ↓
[ESPERANDO PAGO]
    ↓
    ├─→ Cliente PAGA → confirmSale()
    │       ↓
    │   Crear movimientos tipo "venta"
    │   (stock real se descuenta)
    │
    └─→ Cliente CANCELA → releaseReservedStock()
            ↓
        Crear movimientos tipo "cancelacion_reserva"
        (libera la reserva)
```

---

### Flujo 3: Verificar Disponibilidad

```
POST /api/v1/stock-movements/check-availability
{
  "items": [
    { "product_id": 1, "quantity": 5 },
    { "product_id": 2, "quantity": 3 }
  ]
}
    ↓
StockReservationService::checkAvailability()
    ↓
Para cada producto:
    stock_disponible = stock_real - stock_reservado
    ↓
    ¿stock_disponible >= cantidad solicitada?
    ↓
    SI → OK
    NO → Agregar a errores
    ↓
Retornar resultado con errores si los hay
```

---

## Modelo de Datos

### Tabla: stock_movements

```sql
CREATE TABLE stock_movements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    type ENUM('entrada', 'salida', 'ajuste', 'reserva', 'venta', 'cancelacion_reserva'),
    quantity INT NOT NULL,
    stock_before INT NOT NULL,
    stock_after INT NOT NULL,
    reason VARCHAR(255) NULL,
    user_id BIGINT NOT NULL,
    order_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX (product_id, created_at)
);
```

### Campos Explicados

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `product_id` | BIGINT | ID del producto |
| `type` | ENUM | Tipo de movimiento (6 opciones) |
| `quantity` | INT | Cantidad del movimiento (puede ser negativo) |
| `stock_before` | INT | Stock ANTES del movimiento |
| `stock_after` | INT | Stock DESPUÉS del movimiento |
| `reason` | VARCHAR | Motivo/descripción del movimiento |
| `user_id` | BIGINT | Usuario que ejecutó la acción |
| `order_id` | BIGINT | Pedido relacionado (NULL si no aplica) |

---

## Ejemplos de Uso

### Ejemplo 1: Compra de Proveedor

```http
POST /api/v1/products/5/stock
Content-Type: application/json

{
  "type": "entrada",
  "quantity": 100,
  "reason": "Compra proveedor ABC - Factura #12345"
}
```

**Registro creado:**
```json
{
  "product_id": 5,
  "type": "entrada",
  "quantity": 100,
  "stock_before": 50,
  "stock_after": 150,
  "reason": "Compra proveedor ABC - Factura #12345",
  "user_id": 1,
  "order_id": null
}
```

---

### Ejemplo 2: Producto Dañado

```http
POST /api/v1/products/5/stock
Content-Type: application/json

{
  "type": "salida",
  "quantity": 5,
  "reason": "Producto dañado en almacén"
}
```

**Registro creado:**
```json
{
  "product_id": 5,
  "type": "salida",
  "quantity": -5,
  "stock_before": 150,
  "stock_after": 145,
  "reason": "Producto dañado en almacén",
  "user_id": 1,
  "order_id": null
}
```

---

### Ejemplo 3: Inventario Físico

```http
POST /api/v1/products/5/stock
Content-Type: application/json

{
  "type": "ajuste",
  "quantity": 138,
  "reason": "Inventario físico mensual - diferencia de -7 unidades"
}
```

**Registro creado:**
```json
{
  "product_id": 5,
  "type": "ajuste",
  "quantity": 138,
  "stock_before": 145,
  "stock_after": 138,
  "reason": "Inventario físico mensual - diferencia de -7 unidades",
  "user_id": 1,
  "order_id": null
}
```

---

### Ejemplo 4: Verificar Disponibilidad

```http
POST /api/v1/stock-movements/check-availability
Content-Type: application/json

{
  "items": [
    { "product_id": 1, "quantity": 10 },
    { "product_id": 2, "quantity": 5 }
  ]
}
```

**Respuesta (sin problemas):**
```json
{
  "success": true,
  "available": true,
  "errors": []
}
```

**Respuesta (stock insuficiente):**
```json
{
  "success": true,
  "available": false,
  "errors": [
    {
      "product_id": 2,
      "product_name": "Producto Y",
      "requested": 5,
      "available": 3,
      "message": "Stock insuficiente para 'Producto Y'. Disponible: 3, Solicitado: 5"
    }
  ]
}
```

---

### Ejemplo 5: Ver Historial

```http
GET /api/v1/products/5/stock-movements
```

**Respuesta:**
```json
[
  {
    "id": 15,
    "product_id": 5,
    "type": "ajuste",
    "quantity": 138,
    "stock_before": 145,
    "stock_after": 138,
    "reason": "Inventario físico mensual - diferencia de -7 unidades",
    "user_id": 1,
    "user": { "id": 1, "name": "Admin" },
    "order_id": null,
    "created_at": "2025-11-25T10:30:00.000000Z"
  },
  {
    "id": 14,
    "product_id": 5,
    "type": "salida",
    "quantity": -5,
    "stock_before": 150,
    "stock_after": 145,
    "reason": "Producto dañado en almacén",
    "user_id": 1,
    "user": { "id": 1, "name": "Admin" },
    "order_id": null,
    "created_at": "2025-11-25T09:15:00.000000Z"
  },
  {
    "id": 13,
    "product_id": 5,
    "type": "entrada",
    "quantity": 100,
    "stock_before": 50,
    "stock_after": 150,
    "reason": "Compra proveedor ABC - Factura #12345",
    "user_id": 1,
    "user": { "id": 1, "name": "Admin" },
    "order_id": null,
    "created_at": "2025-11-25T08:00:00.000000Z"
  }
]
```

---

## Mejores Prácticas

### 1. Documentar Movimientos

✅ **BIEN:**
```json
{
  "type": "entrada",
  "quantity": 50,
  "reason": "Compra proveedor ABC - Factura #F-2025-001234 - Lote #L789"
}
```

❌ **MAL:**
```json
{
  "type": "entrada",
  "quantity": 50,
  "reason": "compra"
}
```

---

### 2. Usar el Tipo Correcto

```
✅ Llegó mercancía         → ENTRADA
✅ Producto dañado         → SALIDA
✅ Inventario físico       → AJUSTE
✅ Pedido creado           → RESERVA (automático)
✅ Pedido confirmado       → VENTA (automático)
✅ Pedido cancelado        → CANCELACION_RESERVA (automático)
```

---

### 3. Inventarios Periódicos

```javascript
// Hacer inventario físico mensual
// 1. Contar físicamente
const stockReal = 138;

// 2. Ajustar con tipo "ajuste"
POST /products/5/stock
{
  "type": "ajuste",
  "quantity": 138,
  "reason": "Inventario físico noviembre 2025"
}

// ❌ NO usar entrada/salida si no sabes qué pasó
```

---

### 4. Validar Antes de Reservar

```javascript
// Antes de crear un pedido
POST /stock-movements/check-availability
{
  "items": [...]
}

// SI available = true → crear pedido
// SI available = false → mostrar errores al cliente
```

---

### 5. Auditoría y Reportes

```sql
-- Ver todos los movimientos de un producto
SELECT * FROM stock_movements
WHERE product_id = 5
ORDER BY created_at DESC;

-- Ver movimientos por usuario
SELECT * FROM stock_movements
WHERE user_id = 1
ORDER BY created_at DESC;

-- Ver movimientos por tipo
SELECT * FROM stock_movements
WHERE type = 'ajuste'
ORDER BY created_at DESC;

-- Calcular stock reservado actual
SELECT SUM(quantity) as reservado
FROM stock_movements
WHERE product_id = 5
  AND type = 'reserva'
  AND order_id NOT IN (
    SELECT order_id FROM stock_movements
    WHERE type IN ('venta', 'cancelacion_reserva')
  );
```

---

## Seguridad y Validaciones

### Validaciones Implementadas

1. **Stock suficiente para salidas:**
   ```php
   if ($request->type === 'salida' && $request->quantity > $oldStock) {
       return response()->json([
           'message' => 'Stock insuficiente',
           'disponible' => $oldStock,
           'solicitado' => $request->quantity
       ], 400);
   }
   ```

2. **Solo Super Admin puede ajustar stock:**
   ```php
   Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
       Route::post('/products/{id}/stock', [ProductController::class, 'adjustStock']);
   });
   ```

3. **Transacciones para reservas:**
   ```php
   DB::beginTransaction();
   try {
       // Operaciones de stock
       DB::commit();
   } catch (Exception $e) {
       DB::rollBack();
       throw $e;
   }
   ```

4. **Lock optimista para concurrencia:**
   ```php
   $product = Product::lockForUpdate()->findOrFail($id);
   ```

---

## Resumen

| Característica | Descripción |
|----------------|-------------|
| **Tipos de movimiento** | 6 tipos: entrada, salida, ajuste, reserva, venta, cancelación |
| **Trazabilidad** | Total: quién, cuándo, cuánto, por qué |
| **Métodos de ajuste** | Manual (POST /stock) y automático (UPDATE product) |
| **Reservas** | Sistema de reserva virtual para pedidos pendientes |
| **Validaciones** | Stock suficiente, tipos válidos, permisos |
| **Auditoría** | Historial completo con stock antes/después |

---

**Fecha de última actualización:** 25 de noviembre, 2025
