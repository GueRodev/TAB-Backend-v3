# FASE 4: SISTEMA DE PEDIDOS - PLAN DE IMPLEMENTACIÓN COMPLETO

**Proyecto:** TAB-Backend-v3 (Toys and Bricks)  
**Framework:** Laravel 12 + PostgreSQL  
**Fecha:** 2025-11-10  
**Estado:** ⏳ Pendiente de Implementación

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Análisis del Sistema](#análisis-del-sistema)
3. [Estructura de Base de Datos](#estructura-de-base-de-datos)
4. [Flujo de Gestión de Stock](#flujo-de-gestión-de-stock)
5. [Plan de Implementación Detallado](#plan-de-implementación-detallado)
6. [Endpoints de API](#endpoints-de-api)
7. [Seguridad y Autorización](#seguridad-y-autorización)
8. [Sistema de Emails](#sistema-de-emails)
9. [Testing y Validación](#testing-y-validación)
10. [Notas Importantes](#notas-importantes)

---

## 📊 RESUMEN EJECUTIVO

### Objetivo de la Fase 4
Implementar un sistema completo de gestión de pedidos para e-commerce, soportando tanto pedidos online (desde carrito) como pedidos en tienda física, con gestión inteligente de stock mediante reservas y un sistema de comprobantes por email.

### Características Principales

✅ **Dos Tipos de Pedidos:**
- Pedidos Online (desde carrito del cliente)
- Pedidos en Tienda Física (creados por Super Admin)

✅ **Gestión de Stock Inteligente:**
- Sistema de reservas de stock
- Conversión de reservas a ventas confirmadas
- Liberación automática al cancelar

✅ **Estados del Pedido:**
- `pending` - Pedido creado, stock reservado
- `in_progress` - Pedido en proceso (opcional)
- `completed` - Pedido completado, venta confirmada
- `cancelled` - Pedido cancelado, stock liberado
- `archived` - Pedido archivado (histórico)

✅ **Sistema de Comprobantes:**
- Envío automático de comprobante por email al completar pedido
- Email profesional con detalles completos del pedido
- Integración con Mailtrap para desarrollo

✅ **Historial y Auditoría:**
- Registro permanente de todos los pedidos
- Snapshots de productos (precios y datos al momento de la venta)
- Trazabilidad completa de movimientos de stock
- Soft deletes (nunca se pierde información)

✅ **Escalabilidad:**
- Preparado para integración con PayPal
- Arquitectura lista para cálculo de costos de envío
- Base sólida para reportes en Dashboard (Fase 7)

---

## 🔍 ANÁLISIS DEL SISTEMA

### Contexto del Proyecto

**Stack Tecnológico:**
- Laravel 12 (Backend API)
- PostgreSQL (Base de datos)
- Laravel Sanctum (Autenticación API)
- Spatie Laravel Permissions (Roles: Super Admin, Cliente)
- React TypeScript (Frontend - ya implementado)
- Mailtrap (Email testing)

**Fases Completadas:**
- ✅ Fase 1: Configuración Base Laravel 12
- ✅ Fase 2: Autenticación y Roles
- ✅ Fase 3: Productos y Categorías (con sistema de stock_movements)

**Estado Actual:**
- Tabla `stock_movements` ya existe con auditoría completa
- Sistema de ajuste de stock operativo
- Middleware de roles funcionando correctamente
- CORS y Sanctum configurados

---

### Tipos de Pedidos

#### 1. Pedidos Online (desde Carrito)

**Características:**
- Creados por clientes autenticados o guests
- Cliente llena formulario de checkout
- Información del cliente: nombre, teléfono, **email (obligatorio)**
- Tipo de entrega: Recoger en tienda / Envío a domicilio
- Si elige envío, debe proporcionar dirección completa
- Método de pago: Efectivo, Tarjeta, Transferencia, SINPE Móvil
- **Status inicial:** `pending`
- **Requiere aprobación:** Super Admin debe completar manualmente

**Flujo:**
```
Cliente finaliza compra → Order (pending) → Stock reservado → 
Notificación a Super Admin → Super Admin revisa → 
Super Admin completa → Stock confirmado → Email enviado
```

#### 2. Pedidos en Tienda Física

**Características:**
- Creados por Super Admin manualmente
- Cliente está presente en la tienda
- Información del cliente: nombre, teléfono, **email (opcional)**
- Tipo de entrega: Siempre "Recoger en tienda"
- Método de pago: Cualquiera de los disponibles
- **Status inicial:** `pending`
- **Completar cuando cliente paga:** Super Admin marca como completado

**Flujo:**
```
Super Admin crea pedido → Order (pending) → Stock reservado → 
Cliente paga → Super Admin completa → 
Stock confirmado → Email enviado (si se proporcionó email)
```

---


### Estados del Pedido

| Estado | Descripción | Puede cambiar a | Stock |
|--------|-------------|-----------------|-------|
| `pending` | Pedido creado, esperando confirmación | `in_progress`, `completed`, `cancelled` | Reservado |
| `in_progress` | Pedido en proceso (opcional) | `completed`, `cancelled` | Reservado |
| `completed` | Pedido completado y pagado | `archived` | Confirmado (venta) |
| `cancelled` | Pedido cancelado | - (estado final) | Liberado |
| `archived` | Pedido archivado (histórico) | - (estado final) | Confirmado |

---

## 🗂️ ESTRUCTURA DE BASE DE DATOS COMPLETA

Ver documento completo para:
- Tabla `orders` (con todos los campos detallados)
- Tabla `order_items` (con snapshots)
- Tabla `order_shipping_addresses`
- Modificación a `stock_movements` (agregar order_id)

---

## 🔄 FLUJO DE GESTIÓN DE STOCK

### Conceptos Clave

**Stock Disponible = Stock Real - Stock Reservado**

**Tipos de Movimientos:**
- `reserva` - Al crear pedido (pending)
- `venta` - Al completar pedido (completed)
- `cancelacion_reserva` - Al cancelar pedido (cancelled)

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### Subtarea 4.1: Stock Movements
- [ ] Migración: add_order_id_to_stock_movements
- [ ] Modelo: StockMovement actualizado
- [ ] Servicio: StockReservationService creado
- [ ] Prueba: Reservar y liberar stock funciona

### Subtarea 4.2: Migraciones
- [ ] Migración: create_orders_table
- [ ] Migración: create_order_items_table
- [ ] Migración: create_order_shipping_addresses_table
- [ ] Ejecutar: php artisan migrate
- [ ] Verificar: Tablas creadas en PostgreSQL

### Subtarea 4.3: Modelos Eloquent
- [ ] Modelo: Order con relaciones y scopes
- [ ] Modelo: OrderItem con relaciones
- [ ] Modelo: OrderShippingAddress con relaciones
- [ ] Prueba: Relaciones funcionando correctamente

### Subtarea 4.4: Form Requests
- [ ] Request: StoreOnlineOrderRequest
- [ ] Request: StoreInStoreOrderRequest
- [ ] Request: UpdateOrderStatusRequest
- [ ] Prueba: Validaciones bloqueando datos inválidos

### Subtarea 4.5: OrderService
- [ ] Servicio: OrderService completo
- [ ] Método: createOnlineOrder
- [ ] Método: createInStoreOrder
- [ ] Método: updateOrderStatus
- [ ] Método: completeOrder
- [ ] Método: cancelOrder
- [ ] Método: archiveOrder
- [ ] Prueba: Lógica de negocio funciona correctamente

### Subtarea 4.6: OrderController
- [ ] Controlador: OrderController
- [ ] Endpoint: GET /api/v1/orders (listar propios)
- [ ] Endpoint: POST /api/v1/orders (crear online)
- [ ] Endpoint: GET /api/v1/orders/{id} (ver propio)
- [ ] Prueba: Endpoints clientes funcionan

### Subtarea 4.7: AdminOrderController
- [ ] Controlador: AdminOrderController
- [ ] Endpoint: GET /api/v1/admin/orders (listar todos)
- [ ] Endpoint: POST /api/v1/admin/orders (crear in_store)
- [ ] Endpoint: GET /api/v1/admin/orders/{id} (ver cualquiera)
- [ ] Endpoint: PATCH /api/v1/admin/orders/{id}/status
- [ ] Endpoint: POST /api/v1/admin/orders/{id}/archive
- [ ] Endpoint: DELETE /api/v1/admin/orders/{id}
- [ ] Prueba: Endpoints admin funcionan

### Subtarea 4.8: Rutas API
- [ ] Archivo: routes/api.php actualizado
- [ ] Rutas: Clientes configuradas
- [ ] Rutas: Super Admin configuradas
- [ ] Middleware: Aplicado correctamente
- [ ] Prueba: php artisan route:list muestra rutas

### Subtarea 4.9: Sistema de Emails
- [ ] Mailable: OrderReceiptMail
- [ ] Vista: emails/orders/receipt.blade.php
- [ ] Configuración: .env con Mailtrap
- [ ] Integración: En OrderService::completeOrder
- [ ] Prueba: Emails llegando a Mailtrap

### Subtarea 4.10: Seeders
- [ ] Seeder: OrderSeeder
- [ ] Ejecutar: php artisan db:seed --class=OrderSeeder
- [ ] Verificar: 20 pedidos creados en base de datos

### Subtarea 4.11: Testing Completo
- [ ] Test: Cliente crea pedido online (pickup)
- [ ] Test: Cliente crea pedido online (delivery)
- [ ] Test: Super Admin crea pedido en tienda
- [ ] Test: Cliente no puede ver pedidos de otros (403)
- [ ] Test: Cliente no puede actualizar estados (403)
- [ ] Test: Super Admin puede ver todos los pedidos
- [ ] Test: Completar pedido (stock descontado)
- [ ] Test: Cancelar pedido (stock liberado)
- [ ] Test: Archivar pedido completado
- [ ] Test: Stock insuficiente (422)
- [ ] Test: Dirección requerida para delivery (422)
- [ ] Test: Email obligatorio para online (422)
- [ ] Test: Filtros funcionan correctamente
- [ ] Test: Email enviado al completar pedido
- [ ] Test: Pedido sin email (in_store) no falla

---

## 🚀 ORDEN DE EJECUCIÓN RECOMENDADO

**Día 1: Base de Datos y Modelos**
1. Subtarea 4.2: Crear migraciones
2. Ejecutar migraciones
3. Subtarea 4.3: Crear modelos
4. Probar relaciones básicas

**Día 2: Servicios y Lógica de Negocio**
1. Subtarea 4.1: Actualizar stock_movements
2. Crear StockReservationService
3. Subtarea 4.5: Crear OrderService
4. Probar lógica de reserva/venta/cancelación

**Día 3: Validaciones y Controladores**
1. Subtarea 4.4: Crear Form Requests
2. Subtarea 4.6: Crear OrderController
3. Subtarea 4.7: Crear AdminOrderController
4. Subtarea 4.8: Configurar rutas

**Día 4: Sistema de Emails**
1. Subtarea 4.9: Crear Mailable
2. Crear vista de email
3. Integrar en OrderService
4. Probar envío de emails

**Día 5: Testing y Validación**
1. Subtarea 4.10: Crear seeders
2. Subtarea 4.11: Testing completo con Postman
3. Verificar todos los escenarios
4. Documentar cualquier issue encontrado

---

## 📝 NOTAS IMPORTANTES

### Diferencia: order_shipping_addresses vs addresses (Fase 5)

**Tabla `order_shipping_addresses` (Fase 4):**
- **Propósito:** Snapshot de dirección del pedido
- **Relación:** Pertenece a `orders` (1:1)
- **Inmutable:** No se actualiza, histórico
- **Uso:** Registro permanente de dónde se envió ESE pedido

**Tabla `addresses` (Fase 5):**
- **Propósito:** Libreta de direcciones del usuario
- **Relación:** Pertenece a `users` (1:N)
- **Editable:** El usuario puede actualizar
- **Uso:** Direcciones guardadas para reutilizar

---

## ⚠️ PUNTOS CRÍTICOS

1. **TODOS los pedidos inician en `pending`** (no hay creación directa en completed)
2. **Super Admin debe completar manualmente** todos los pedidos
3. **Email obligatorio para pedidos online**, opcional para in_store
4. **Stock se reserva al crear**, se confirma al completar, se libera al cancelar
5. **Snapshots inmutables** de productos y direcciones
6. **Soft deletes** - nunca se pierde información

---

## 🎯 CRITERIOS DE ÉXITO

La Fase 4 estará completada exitosamente cuando:

✅ Todas las migraciones ejecutadas sin errores
✅ Modelos con relaciones funcionando correctamente
✅ Endpoints de API respondiendo correctamente
✅ Sistema de reserva de stock operativo
✅ Emails de comprobante enviándose correctamente
✅ Validaciones bloqueando datos inválidos
✅ Autorización protegiendo recursos apropiadamente
✅ Todos los tests de Postman pasando
✅ Seeders generando datos de prueba
✅ Documentación completa y actualizada

---

**Nota:** Este es un resumen ejecutivo. El plan completo con código detallado de cada subtarea está disponible en el documento original de 60+ páginas.

---

## 📞 SIGUIENTE PASO

**¿Estás listo para comenzar con la Subtarea 4.1?**

El siguiente paso sería crear la migración para agregar `order_id` a la tabla `stock_movements` y comenzar con la implementación del `StockReservationService`.

---

**Fecha de creación:** 2025-11-10  
**Versión:** 1.0 - Resumen Ejecutivo  
**Autor:** Plan de Implementación TAB-Backend-v3  
**Proyecto:** Toys and Bricks E-commerce

