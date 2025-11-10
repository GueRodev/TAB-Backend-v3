# Plan de Implementación Backend Laravel 12

**Versión:** 1.0  
**Fecha:** 2025-10-24  
**Stack:** Laravel 12 + PostgreSQL + React TypeScript + Vite  
**Gestión de Roles:** Laravel Spatie Permissions

---

## 📊 Análisis del Repositorio

Estado actual del proyecto:

- ✅ **Frontend completo** en React + TypeScript con arquitectura modular
- ✅ **Documentación extensa** de esquemas de base de datos (Laravel y Supabase)
- ✅ **Estructura de API** preparada en `/src/api/`
- ✅ **Tipos TypeScript** definidos para todas las entidades
- ✅ **Configuración** lista para conectar con backend

---

## 🎯 Fases de Implementación

El trabajo se divide en **6 fases iterativas**. Cada fase debe completarse antes de avanzar a la siguiente.

---

## Fase 1: Configuración Base de Laravel 12

**Objetivo:** Establecer la fundación del proyecto

### Subtareas:

1. **Instalación y configuración inicial de Laravel 12**
2. **Configuración de PostgreSQL**
3. **Configuración de CORS para conectar con frontend**
4. **Configuración de Laravel Sanctum para autenticación**
5. **Estructura de carpetas y arquitectura**

---

## Fase 2: Sistema de Autenticación y Roles

**Objetivo:** Base crítica para toda la aplicación

### 📦 Laravel Spatie Permissions

Se utilizará `spatie/laravel-permission` para gestionar roles y permisos de manera profesional y escalable.

**Roles Iniciales:**
- **Super Admin** - Usuario administrador con acceso total al sistema
- **Cliente** - Usuario final que realiza compras

**Escalabilidad:** La arquitectura permite agregar más roles en el futuro sin modificar la estructura base.

### Subtareas:

1. **Instalación de Laravel Spatie Permissions**
   - Instalación del paquete via Composer
   - Publicación de configuración y migraciones
   - Ejecución de migraciones (roles, permissions, model_has_roles)

2. **Configuración de Roles y Permisos**
   - Seeder para crear roles: Super Admin y Cliente
   - Definición de permisos por módulo (productos, pedidos, usuarios)
   - Asignación de permisos a rol Super Admin

3. **Migración de tabla users**
   - Campos básicos: name, email, password, phone
   - Integración con trait HasRoles de Spatie

4. **Modelo User con Spatie**
   - Uso del trait HasRoles
   - Métodos helper: hasRole(), can(), hasPermissionTo()

5. **AuthController (Login, Register, Logout)**
   - Endpoint de registro con asignación automática de rol Cliente
   - Login con retorno de roles y permisos en respuesta
   - Logout con revocación de tokens Sanctum

6. **Middleware de autenticación y roles**
   - Middleware role: para verificar roles específicos
   - Middleware permission: para verificar permisos puntuales
   - Protección de rutas admin con middleware role:Super Admin

7. **Rutas de autenticación protegidas**
   - Rutas públicas: login, register
   - Rutas protegidas: profile, logout (auth:sanctum)
   - Rutas admin: gestión de productos, usuarios (role:Super Admin)

8. **Integración con frontend (AuthContext)**
   - Actualizar AuthContext para recibir roles desde API
   - Guardar roles/permisos en estado global
   - Helpers en frontend: canAccess(), hasRole()

---

## Fase 3: Gestión de Productos y Categorías

**Objetivo:** CRUD completo del catálogo

### Subtareas:

1. **Migraciones de categories, subcategories, products**
2. **Modelos Eloquent con relaciones**
3. **ProductController y CategoryController**
   - Protección con middleware role:Super Admin en rutas de escritura
4. **Validaciones con Form Requests**
5. **Rutas API RESTful**
   - GET /products - Público (clientes pueden ver)
   - POST/PUT/DELETE /products - Protegido (solo Super Admin)
6. **Integración con frontend (ProductsContext)**

---

## Fase 4: Sistema de Pedidos

**Objetivo:** Funcionalidad crítica del e-commerce

### Subtareas:

1. **Migraciones de orders y order_items**
2. **Modelo Order con relaciones**
3. **OrderController con lógica de negocio**
   - Clientes solo pueden ver sus propios pedidos
   - Super Admin puede ver y gestionar todos los pedidos
4. **Cálculo de totales y validaciones**
5. **Integración con frontend (OrderContext)**

---

## Fase 5: Direcciones y Carrito

**Objetivo:** Completar experiencia de usuario

### Subtareas:

1. **Migración de addresses y cart_items**
2. **AddressController y CartController**
   - Usuarios solo pueden gestionar sus propias direcciones y carrito
3. **Integración con frontend**

---

## Fase 6: Sistema de Notificaciones

**Objetivo:** Notificaciones persistentes para administradores (sin tiempo real)

### 📦 Arquitectura de Notificaciones

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

### Subtareas:

1. **Migración de tabla notifications**
   - Campos: id (UUID), type, title, message, user_id, data (JSON), read_at, timestamps
   - Índices en user_id y created_at para performance
   - Relación con tabla users

2. **Modelo Notification con Eloquent**
   - Relación belongsTo con User
   - Scopes: unread(), byType(), recent()
   - Cast de campo data a array
   - Métodos: markAsRead(), markAsUnread()

3. **NotificationController**
   - GET /api/notifications - Listar notificaciones del usuario autenticado
   - GET /api/notifications/unread - Solo no leídas (para polling)
   - GET /api/notifications/unread/count - Contador para badge
   - PATCH /api/notifications/{id}/read - Marcar una como leída
   - PATCH /api/notifications/read-all - Marcar todas como leídas
   - DELETE /api/notifications/{id} - Eliminar notificación específica
   - Middleware: auth:sanctum, role:Super Admin

4. **Integración en Controllers existentes**
   - OrderController: Crear notificación en store() cuando se crea pedido
   - OrderController: Crear notificación en archive() cuando se archiva
   - UserController: Crear notificación en deactivate() y activate()
   - Helper: NotificationService para centralizar lógica de creación

5. **Rutas API protegidas**
   ```php
   Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
       Route::get('/notifications', [NotificationController::class, 'index']);
       Route::get('/notifications/unread', [NotificationController::class, 'unread']);
       Route::get('/notifications/unread/count', [NotificationController::class, 'unreadCount']);
       Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
       Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
       Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
   });
   ```

6. **Validaciones y Form Requests**
   - Validar que user_id corresponda a Super Admin
   - Validar tipos de notificaciones permitidos

7. **Frontend: Actualizar NotificationsContext**
   - Reemplazar localStorage por API calls
   - Implementar polling cada 30 segundos con setInterval
   - Consultar endpoint /api/notifications/unread
   - Actualizar badge con contador de no leídas
   - Manejar estados: loading, error, success

8. **Frontend: Integrar con componentes existentes**
   - Actualizar NotificationsPopover para usar API
   - Implementar markAsRead al hacer click
   - Implementar "Marcar todas como leídas"
   - Mostrar loading state durante polling

9. **Testing y validación**
   - Probar creación de notificaciones en cada evento
   - Verificar que solo Super Admin recibe notificaciones
   - Validar polling funciona correctamente
   - Probar mark as read/unread
   - Testing de endpoints con Postman

### Polling Implementation (Frontend)

```typescript
// NotificationsContext.tsx
const POLL_INTERVAL = 30000; // 30 segundos

useEffect(() => {
  const fetchUnreadNotifications = async () => {
    try {
      const data = await notificationsService.getUnread();
      setNotifications(data);
      setUnreadCount(data.length);
    } catch (error) {
      console.error('Error fetching notifications:', error);
    }
  };

  // Fetch inicial
  fetchUnreadNotifications();

  // Polling cada 30 segundos
  const interval = setInterval(fetchUnreadNotifications, POLL_INTERVAL);

  return () => clearInterval(interval);
}, []);
```

### Notas Importantes Fase 6

- ⚠️ **NO usar Laravel Broadcasting** ni Pusher en esta fase
- ✅ Las notificaciones persisten en DB (no se pierden)
- ✅ Polling de 30 segundos es suficiente para e-commerce
- ✅ Arquitectura permite migrar a tiempo real después
- ✅ Solo Super Admin recibe notificaciones del sistema

---

## 🔐 Estructura de Roles y Permisos

### Roles Definidos

| Rol | Descripción | Permisos Principales |
|-----|-------------|---------------------|
| **Super Admin** | Administrador del sistema | Gestión completa de productos, categorías, todos los pedidos, usuarios y acceso total al dashboard |
| **Cliente** | Usuario final que compra | Ver catálogo, crear pedidos, ver sus propios pedidos, gestionar perfil, direcciones y carrito |

### Permisos por Módulo

**Leyenda:** ✅ = PERMITIDO | ❌ = DENEGADO

| Módulo | Permiso | Super Admin | Cliente |
|--------|---------|-------------|---------|
| **Productos** | Ver productos | ✅ | ✅ |
| **Productos** | Crear productos | ✅ | ❌ |
| **Productos** | Editar productos | ✅ | ❌ |
| **Productos** | Eliminar productos | ✅ | ❌ |
| **Pedidos** | Ver todos los pedidos | ✅ | ❌ |
| **Pedidos** | Ver propios pedidos | ✅ | ✅ |
| **Pedidos** | Crear pedidos | ✅ | ✅ |
| **Pedidos** | Actualizar estado | ✅ | ❌ |
| **Usuarios** | Gestionar usuarios | ✅ | ❌ |
| **Usuarios** | Ver perfil propio | ✅ | ✅ |
| **Usuarios** | Editar perfil propio | ✅ | ✅ |

### Explicación de Permisos

**Super Admin** tiene acceso total al sistema:
- Puede gestionar productos (crear, editar, eliminar)
- Puede ver TODOS los pedidos de todos los usuarios
- Puede actualizar el estado de pedidos
- Puede gestionar usuarios del sistema

**Cliente** tiene permisos limitados a sus propias acciones:
- Solo puede VER el catálogo de productos (no modificarlos)
- Solo puede ver SUS PROPIOS pedidos (no los de otros)
- Puede crear nuevos pedidos (comprar)
- Solo puede gestionar su propio perfil y direcciones

---

## 🚀 Inicio de Implementación

### Fase 1: Próximos Pasos

1. Verificar la documentación oficial de Laravel 12
2. Configurar el proyecto base
3. Configurar la conexión a PostgreSQL
4. Configurar CORS
5. Instalar y configurar Laravel Sanctum
6. Instalar y configurar Laravel Spatie Permissions

### Resultado Esperado Fase 1

Una vez completada la Fase 1, tendrás:
- ✅ Laravel 12 funcional
- ✅ Conectado a PostgreSQL
- ✅ CORS configurado
- ✅ Sanctum listo para autenticación
- ✅ Spatie Permissions configurado para gestión de roles

---

## 📝 Notas Importantes

- **Enfoque iterativo:** No avanzar a la siguiente fase sin completar la actual
- **Validación continua:** Probar cada subtarea antes de continuar
- **Documentación:** Mantener código documentado y seguir convenciones de Laravel 12
- **Seguridad:** Todos los endpoints críticos protegidos con middleware apropiado
- **Escalabilidad:** Arquitectura preparada para crecer sin refactorización mayor

---

**Documento generado:** 2025-10-24  
**Laravel 12 + Spatie Permissions + React TypeScript**
