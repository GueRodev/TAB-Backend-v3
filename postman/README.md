# Colección de Postman - Sistema de Pedidos TAB Backend

Esta colección de Postman proporciona todas las pruebas necesarias para validar el sistema de pedidos de Toys and Bricks Backend API v3.

## 📋 Contenido de la Colección

### Archivos Incluidos

- **`TAB_Backend_Orders_System.postman_collection.json`**: Colección principal con todos los endpoints
- **`TAB_Environment.postman_environment.json`**: Variables de entorno pre-configuradas
- **`README.md`**: Este archivo con instrucciones de uso

### Endpoints Incluidos

1. **Autenticación** (2 requests)
   - Login Cliente
   - Login Admin

2. **Rutas de Cliente** (7 requests)
   - Listar pedidos
   - Crear pedido online (delivery/pickup)
   - Ver detalles de pedido
   - Casos de error (validaciones)

3. **Rutas de Admin** (9 requests)
   - Listar todos los pedidos
   - Crear pedido en tienda física
   - Cambiar estados (in_progress, complete, cancel, archive)
   - Eliminar pedido

4. **Flujos de Prueba Completos** (3 flujos)
   - Flujo 1: Pedido online exitoso completo
   - Flujo 2: Pedido cancelado
   - Flujo 3: Pedido en tienda física

---

## 🚀 Configuración Inicial

### Paso 1: Importar la Colección

1. Abre Postman
2. Click en **Import** (botón superior izquierdo)
3. Arrastra o selecciona el archivo `TAB_Backend_Orders_System.postman_collection.json`
4. Click en **Import**

### Paso 2: Importar el Entorno

1. Click en **Import** nuevamente
2. Selecciona el archivo `TAB_Environment.postman_environment.json`
3. Click en **Import**

### Paso 3: Activar el Entorno

1. En la esquina superior derecha, selecciona el dropdown de entornos
2. Selecciona **"TAB Backend - Local Development"**
3. El entorno ahora está activo (aparecerá con fondo naranja)

### Paso 4: Verificar Variables de Entorno

Click en el ícono del ojo 👁️ al lado del selector de entorno para ver las variables:

| Variable | Valor Inicial | Descripción |
|----------|---------------|-------------|
| `base_url` | `http://localhost:8000` | URL base de la API |
| `token_cliente` | (vacío) | Se llenará automáticamente al hacer login |
| `token_admin` | (vacío) | Se llenará automáticamente al hacer login |
| `order_id` | (vacío) | ID del último pedido creado |
| `test_order_id` | (vacío) | Para flujos de prueba |
| `cancel_order_id` | (vacío) | Para pruebas de cancelación |
| `instore_order_id` | (vacío) | Para pedidos en tienda |

---

## 🔑 Preparación del Backend

Antes de ejecutar las pruebas, asegúrate de que el backend esté configurado:

### 1. Ejecutar Migraciones

```bash
cd /home/user/TAB-Backend-v3
php artisan migrate
```

### 2. Poblar Base de Datos (Opcional)

```bash
# Ejecutar seeders si necesitas datos de prueba
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=OrderSeeder
```

### 3. Configurar Emails (Opcional)

Edita el archivo `.env` para configurar emails en modo log:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@toysandbricks.com
MAIL_FROM_NAME="Toys and Bricks"
```

### 4. Iniciar el Servidor

```bash
php artisan serve
```

El servidor debe estar corriendo en `http://localhost:8000`

---

## 📝 Guía de Uso Paso a Paso

### PASO 1: Obtener Tokens de Autenticación

#### 1.1. Login como Cliente

1. Abre la carpeta **"0. Autenticación"**
2. Selecciona el request **"Login Cliente"**
3. Revisa el body (puedes cambiar email/password si es necesario):
   ```json
   {
       "email": "cliente@example.com",
       "password": "password"
   }
   ```
4. Click en **Send**
5. ✅ El token se guardará automáticamente en `token_cliente`

**Respuesta esperada:**
```json
{
    "success": true,
    "data": {
        "token": "1|abc123...",
        "user": {...}
    }
}
```

#### 1.2. Login como Admin

1. Selecciona el request **"Login Admin"**
2. Revisa el body:
   ```json
   {
       "email": "admin@example.com",
       "password": "password"
   }
   ```
3. Click en **Send**
4. ✅ El token se guardará automáticamente en `token_admin`

> **Nota:** Si recibes error 401, verifica que los usuarios existan en la base de datos con los roles correctos.

---

### PASO 2: Probar Rutas de Cliente

#### 2.1. Listar Pedidos del Cliente

1. Abre la carpeta **"1. Rutas de Cliente"**
2. Selecciona **"Listar Pedidos del Cliente"**
3. Click en **Send**
4. Verifica que retorna solo pedidos del cliente autenticado

#### 2.2. Crear Pedido Online con Delivery

1. Selecciona **"Crear Pedido Online - Delivery"**
2. Revisa el body JSON (puedes modificar product_id según tu BD):
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
           }
       ],
       "shipping_address": {
           "province": "San José",
           "canton": "Escazú",
           "district": "San Rafael",
           "address_details": "100m norte de la iglesia"
       }
   }
   ```
3. Click en **Send**
4. ✅ El `order_id` se guardará automáticamente en las variables

**Respuesta esperada (201):**
```json
{
    "success": true,
    "message": "Pedido creado exitosamente",
    "data": {
        "id": 15,
        "order_number": "ORD-20251113-0015",
        "status": "pending",
        "total": "52500.00"
    }
}
```

#### 2.3. Ver Detalles del Pedido

1. Selecciona **"Ver Detalles de Pedido"**
2. Nota que usa `{{order_id}}` automáticamente
3. Click en **Send**
4. Verifica que muestra items, dirección y movimientos de stock

#### 2.4. Probar Validaciones de Error

Ejecuta los requests de error para verificar validaciones:

- **"Error - Pedido sin Items"**: Debe retornar 422
- **"Error - Delivery sin Address"**: Debe retornar 422

---

### PASO 3: Probar Rutas de Admin

#### 3.1. Listar Todos los Pedidos (Admin)

1. Abre la carpeta **"2. Rutas de Admin"**
2. Selecciona **"Listar Todos los Pedidos"**
3. Click en **Send**
4. Verifica que admin puede ver pedidos de todos los clientes

#### 3.2. Crear Pedido en Tienda Física

1. Selecciona **"Crear Pedido en Tienda Física"**
2. Click en **Send**
3. Verifica que `order_type` es "in_store" y `delivery_option` es "pickup"

#### 3.3. Cambiar Estados del Pedido

Ejecuta en orden usando el último `order_id` creado:

1. **"Marcar Pedido como En Progreso"**
   - Status cambia: `pending` → `in_progress`

2. **"Completar Pedido"**
   - Status cambia: `in_progress` → `completed`
   - ✅ Stock se descuenta físicamente
   - ✅ Email se envía (verificar en `storage/logs/laravel.log`)

3. **"Archivar Pedido"**
   - Status cambia: `completed` → `archived`

#### 3.4. Probar Cancelación

1. Crea un nuevo pedido (usa cualquier request de creación)
2. Selecciona **"Cancelar Pedido"**
3. Click en **Send**
4. ✅ Stock reservado se libera automáticamente

---

### PASO 4: Ejecutar Flujos Completos

La carpeta **"3. Flujos de Prueba Completos"** contiene escenarios end-to-end.

#### Flujo 1: Pedido Online Exitoso

Ejecuta los requests en orden (1 → 5):

1. **1. Crear Pedido Online**
   - Cliente crea pedido
   - Stock se reserva (NO se descuenta aún)

2. **2. Admin - Marcar como En Progreso**
   - Status: `pending` → `in_progress`

3. **3. Admin - Completar Pedido**
   - Stock se descuenta físicamente
   - Email se envía al cliente
   - Status: `in_progress` → `completed`

4. **4. Cliente - Ver Pedido Completado**
   - Cliente consulta su pedido
   - Puede ver todos los detalles

5. **5. Admin - Archivar Pedido**
   - Status: `completed` → `archived`

#### Flujo 2: Pedido Cancelado

Ejecuta los requests en orden (1 → 3):

1. **1. Crear Pedido para Cancelar**
2. **2. Admin - Marcar En Progreso**
3. **3. Admin - Cancelar Pedido**
   - ✅ Stock reservado se libera
   - Movimiento de stock tipo "cancelacion_reserva" se crea

#### Flujo 3: Pedido en Tienda

Ejecuta los requests en orden (1 → 2):

1. **1. Admin - Crear Pedido en Tienda**
   - order_type: "in_store"
   - delivery_option: "pickup"
   - shipping_cost: 0

2. **2. Admin - Completar Inmediatamente**
   - Flujo rápido para ventas en tienda física

---

## ✅ Tests Automáticos

Cada request incluye tests automáticos que se ejecutan después de enviar el request.

### Ver Resultados de Tests

Después de enviar un request, ve a la pestaña **"Test Results"** en la parte inferior:

- ✅ **Verde**: Test pasó correctamente
- ❌ **Rojo**: Test falló

### Ejemplos de Tests Incluidos

```javascript
// Verifica status code
pm.test("Status code is 200", () => {
    pm.response.to.have.status(200);
});

// Verifica estructura de respuesta
pm.test("Has success property", () => {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success', true);
});

// Guarda variables automáticamente
if (pm.response.code === 201) {
    const jsonData = pm.response.json();
    pm.environment.set("order_id", jsonData.data.id);
}
```

---

## 🔍 Verificar Sistema de Stock

### Consultar Movimientos de Stock

Después de crear/completar/cancelar un pedido, verifica en la base de datos:

```sql
-- Ver movimientos de un pedido específico
SELECT * FROM stock_movements
WHERE order_id = {order_id}
ORDER BY created_at ASC;

-- Ver stock actual de un producto
SELECT id, name, stock FROM products WHERE id = 1;
```

### Tipos de Movimientos Esperados

| Acción | Tipo de Movimiento | Quantity | Efecto en Stock Físico |
|--------|-------------------|----------|----------------------|
| Crear pedido | `reserva` | Negativo (-2) | No cambia |
| Completar pedido | `venta` | Negativo (-2) | Descuenta |
| Cancelar pedido | `cancelacion_reserva` | Positivo (+2) | No cambia |

---

## 📧 Verificar Emails

Los emails se guardan en modo log:

```bash
# Ver el log de Laravel
tail -f storage/logs/laravel.log

# Buscar emails de pedidos
grep "Comprobante de Pedido" storage/logs/laravel.log
```

El email debe incluir:
- Número de pedido
- Nombre del cliente
- Lista de productos con precios
- Subtotal, envío y total
- Dirección de envío (si aplica)
- Método de pago

---

## 🐛 Solución de Problemas

### Error 401 - Unauthenticated

**Causa:** Token no válido o expirado

**Solución:**
1. Ejecuta nuevamente el request de login
2. Verifica que el token se guardó en las variables de entorno
3. Verifica que estás usando el token correcto (cliente vs admin)

### Error 403 - Forbidden

**Causa:** Usuario no tiene permisos

**Solución:**
- Verifica que estés usando `token_admin` para rutas de admin
- Verifica que estés usando `token_cliente` para rutas de cliente

### Error 404 - Not Found

**Causa:** Pedido no existe o fue eliminado

**Solución:**
1. Crea un nuevo pedido
2. Verifica que `order_id` tiene un valor válido
3. Ejecuta el request de listar pedidos para ver IDs disponibles

### Error 422 - Validation Error

**Causa:** Datos inválidos en el request

**Solución:**
1. Lee el mensaje de error en la respuesta
2. Verifica que `product_id` existe en la base de datos
3. Verifica que hay stock suficiente
4. Verifica campos requeridos según el tipo de pedido

### Error 500 - Server Error

**Causa:** Error interno del servidor

**Solución:**
1. Revisa los logs del backend: `tail -f storage/logs/laravel.log`
2. Verifica que la base de datos esté corriendo
3. Verifica que las migraciones se ejecutaron correctamente

---

## 📊 Checklist de Testing Completo

Marca cada item después de probarlo:

### Rutas de Cliente
- [ ] GET /orders - Listar pedidos del cliente
- [ ] GET /orders?status=completed - Filtro por estado
- [ ] POST /orders (delivery) - Crear pedido con envío
- [ ] POST /orders (pickup) - Crear pedido para recoger
- [ ] GET /orders/{id} - Ver detalles de pedido propio
- [ ] POST /orders sin items - Retorna error 422
- [ ] POST /orders (delivery) sin address - Retorna error 422

### Rutas de Admin
- [ ] GET /admin/orders - Listar todos los pedidos
- [ ] GET /admin/orders?status=pending - Filtro funciona
- [ ] POST /admin/orders - Crear pedido en tienda
- [ ] GET /admin/orders/{id} - Ver cualquier pedido
- [ ] PATCH /admin/orders/{id}/mark-in-progress - Cambiar estado
- [ ] PATCH /admin/orders/{id}/complete - Completar pedido
- [ ] PATCH /admin/orders/{id}/cancel - Cancelar pedido
- [ ] POST /admin/orders/{id}/archive - Archivar pedido
- [ ] DELETE /admin/orders/{id} - Eliminar pedido

### Sistema de Stock
- [ ] Crear pedido reserva stock (no descuenta)
- [ ] Completar pedido descuenta stock físico
- [ ] Cancelar pedido libera stock reservado
- [ ] Stock insuficiente impide crear pedido

### Sistema de Emails
- [ ] Email se envía al completar pedido
- [ ] Email contiene información correcta
- [ ] Email visible en storage/logs/laravel.log

### Flujos Completos
- [ ] Flujo 1: Pedido online exitoso completo
- [ ] Flujo 2: Pedido cancelado
- [ ] Flujo 3: Pedido en tienda física

---

## 🎯 Próximos Pasos

Después de completar todas las pruebas:

1. **Documentar Resultados**: Anota cualquier bug o comportamiento inesperado
2. **Verificar Base de Datos**: Ejecuta las queries SQL del documento de testing
3. **Revisar Logs**: Verifica que no haya errores en `storage/logs/laravel.log`
4. **Testing Adicional**: Prueba casos edge:
   - Productos sin stock
   - Cantidades negativas
   - Transiciones de estado inválidas
   - Acceso no autorizado

---

## 📚 Referencias

- **Documento de Testing Completo**: `docs/TESTING_SISTEMA_PEDIDOS.md`
- **Rutas de API**:
  - Cliente: `routes/v1/orders.php`
  - Admin: `routes/v1/admin_orders.php`

---

## 💡 Tips Adicionales

### Ejecutar Múltiples Requests

Usa el **Collection Runner** de Postman:
1. Click derecho en la colección
2. Selecciona **"Run collection"**
3. Selecciona los requests que quieras ejecutar
4. Click en **"Run"**

### Guardar Respuestas como Ejemplos

Para documentar respuestas:
1. Después de enviar un request exitoso
2. Click en **"Save Response"** → **"Save as example"**
3. Los ejemplos aparecerán en la documentación de la colección

### Exportar Variables

Para guardar tokens entre sesiones:
1. Click en el ícono del ojo 👁️
2. Click en **"Edit"** al lado del entorno
3. Copia los valores de `token_cliente` y `token_admin`
4. Pégalos en un lugar seguro

---

## 🤝 Soporte

Si encuentras problemas o tienes preguntas:
1. Revisa la sección de **Solución de Problemas** arriba
2. Consulta el documento `docs/TESTING_SISTEMA_PEDIDOS.md`
3. Revisa los logs del backend

---

**¡Feliz Testing! 🚀**
