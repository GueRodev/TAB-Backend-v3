# 🚀 Plan de Despliegue - Backend TAB en Laravel Cloud (Staging)

> **Ambiente:** Staging
> **Rama:** `staging`
> **Frontend:** Netlify
> **Dominio Final:** toysandbricks.store (configurar después de pruebas)

---

## 📋 CHECKLIST DE DESPLIEGUE

### FASE 1: Preparación del Repositorio y Código

#### 1.1 Verificar Dependencias
- [ ] Verificar que `league/flysystem-aws-s3-v3` esté instalado (✅ Ya está en composer.json)
- [ ] Asegurar que la rama `staging` esté actualizada y lista para desplegar
- [ ] Verificar que no haya commits pendientes

**Comando de verificación:**
```bash
cd Backend-Api-TAB-v3
git status
git checkout staging
git pull origin staging
composer show league/flysystem-aws-s3-v3
```

#### 1.2 Commit de Cambios de Configuración
- [ ] Hacer commit de los archivos actualizados:
  - `config/filesystems.php` (configuración S3)
  - `app/Services/ProductImageService.php` (soporte multi-disk)
  - `config/cors.php` (dominios Netlify y staging)
  - `.env.example` (template completo para staging)

**Comandos:**
```bash
git add config/filesystems.php
git add app/Services/ProductImageService.php
git add config/cors.php
git add .env.example
git commit -m "Configure object storage and CORS for Laravel Cloud staging deployment

- Add S3 disk configuration for Laravel Cloud Object Storage
- Update ProductImageService to support both local and S3 storage
- Add Netlify domains to CORS allowed origins
- Update .env.example with staging configuration template

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>"
git push origin staging
```

---

### FASE 2: Crear Aplicación en Laravel Cloud

#### 2.1 Conectar Repositorio
- [ ] Ir a [Laravel Cloud](https://cloud.laravel.com)
- [ ] Seleccionar tu organización (o crear una si no existe)
- [ ] Click en "Create Application" → "From Existing Repository"
- [ ] Conectar tu proveedor Git (GitHub/Bitbucket/GitLab)
- [ ] Seleccionar el repositorio: `GueRodev/TAB-Backend-v3`

#### 2.2 Configurar Aplicación
- [ ] **Application Name:** `TAB-Backend-v3`
- [ ] **Region:** `US East (Ohio)` (según las imágenes que mostraste)
- [ ] **Branch:** `staging`
- [ ] Click "Create Application"

**Resultado esperado:**
- Laravel Cloud crea automáticamente un environment "staging"
- Se genera un dominio automático: `tab-backend-v3-staging-jmuzni.laravel.cloud`

---

### FASE 3: Configurar Base de Datos PostgreSQL

#### 3.1 Crear Base de Datos
- [ ] En el dashboard de tu app, ir a la pestaña "Environment"
- [ ] En la sección "App cluster", click en "Add resource"
- [ ] Seleccionar "Database" → "Create or connect a database"
- [ ] Seleccionar "Serverless Postgres"
- [ ] Click "Create"

#### 3.2 Verificar Variables de Entorno Inyectadas
Laravel Cloud inyecta automáticamente estas variables (NO necesitas configurarlas manualmente):
- [ ] `DB_CONNECTION=pgsql`
- [ ] `DB_HOST=ep-xxx.us-east-2.pg.laravel.cloud`
- [ ] `DB_PORT=5432`
- [ ] `DB_DATABASE=xxx`
- [ ] `DB_USERNAME=xxx`
- [ ] `DB_PASSWORD=xxx`

**Verificar en:** Settings → General → Custom environment variables (click "Reveal secrets")

---

### FASE 4: Configurar Object Storage (Bucket S3/R2)

#### 4.1 Crear Bucket
- [ ] En el dashboard, ir a "Add resource"
- [ ] Seleccionar "Bucket" → "Create or connect object storage"
- [ ] Seleccionar "Laravel Object Storage" (Cloudflare R2)
- [ ] **Disk name:** `s3` (importante que sea exactamente "s3")
- [ ] **File visibility:** `Public` (para que las imágenes sean accesibles)
- [ ] Click "Create"

#### 4.2 Configurar CORS del Bucket
Laravel Cloud gestiona automáticamente las políticas CORS del bucket.

- [ ] En el bucket creado, ir a "Settings" → "Allowed origins"
- [ ] Agregar origenes permitidos:
  - `http://localhost:8081` (desarrollo local)
  - `http://localhost:3000` (desarrollo local)
  - `http://localhost:5173` (desarrollo local)
  - Tu dominio de Netlify (ej: `https://tab-frontend-staging.netlify.app`)
  - ⚠️ **NOTA:** Cuando configures el dominio final, agregar:
    - `https://toysandbricks.store`
    - `https://www.toysandbricks.store`

#### 4.3 Verificar Variables de Entorno Inyectadas
Laravel Cloud inyecta automáticamente estas variables (NO necesitas configurarlas manualmente):
- [ ] `FILESYSTEM_DISK=s3`
- [ ] `AWS_ACCESS_KEY_ID=xxx`
- [ ] `AWS_SECRET_ACCESS_KEY=xxx`
- [ ] `AWS_DEFAULT_REGION=auto`
- [ ] `AWS_BUCKET=xxx`
- [ ] `AWS_ENDPOINT=https://xxx.r2.cloudflarestorage.com`
- [ ] `AWS_URL=https://xxx.r2.cloudflarestorage.com/xxx`

**Verificar en:** Settings → General → Custom environment variables (click "Reveal secrets")

#### 4.4 Redesplegar Aplicación
**⚠️ IMPORTANTE:** Después de adjuntar el bucket, debes redesplegar para que los cambios surtan efecto.

- [ ] Click en "Deploy" en el dashboard
- [ ] Esperar a que el deployment complete

---

### FASE 5: Configurar Variables de Entorno Adicionales

#### 5.1 Ir a Settings → General → Custom environment variables

#### 5.2 Agregar Variables Manualmente
**IMPORTANTE:** Solo agrega las variables que NO son inyectadas automáticamente por Laravel Cloud.

```env
# Aplicación
APP_NAME="Toys and Bricks API"
APP_ENV=staging
APP_DEBUG=false
APP_TIMEZONE=America/Costa_Rica
APP_URL=https://tab-backend-v3-staging-jmuzni.laravel.cloud
FRONTEND_URL=https://your-netlify-app.netlify.app

# Locale
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CR

# Logs
LOG_LEVEL=info

# Email - Hostinger SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=info@toysandbricks.store
MAIL_PASSWORD=tu-password-de-hostinger-aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@toysandbricks.store
MAIL_FROM_NAME="Toys and Bricks"

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_STORE=database

# Sesión
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

**Checklist de variables:**
- [ ] `APP_NAME`
- [ ] `APP_ENV=staging`
- [ ] `APP_DEBUG=false`
- [ ] `APP_TIMEZONE=America/Costa_Rica`
- [ ] `APP_URL` (dominio de Laravel Cloud)
- [ ] `FRONTEND_URL` (dominio de Netlify)
- [ ] `APP_LOCALE=es`
- [ ] `APP_FALLBACK_LOCALE=es`
- [ ] `APP_FAKER_LOCALE=es_CR`
- [ ] `LOG_LEVEL=info`
- [ ] Variables de email Hostinger (MAIL_*)
- [ ] `QUEUE_CONNECTION=database`
- [ ] `CACHE_STORE=database`
- [ ] `SESSION_DRIVER=database`

#### 5.3 Generar APP_KEY
- [ ] Laravel Cloud genera automáticamente el `APP_KEY` al crear la aplicación
- [ ] Verificar que exista en las variables de entorno

---

### FASE 6: Ejecutar Migraciones y Seeders

#### 6.1 Acceder a Commands
- [ ] En el dashboard, ir a la pestaña "Commands"
- [ ] Encontrarás un campo de texto para ejecutar comandos Artisan

#### 6.2 Ejecutar Migraciones
- [ ] Ejecutar el siguiente comando:
```bash
php artisan migrate --force
```

**Esperar a que complete.** Deberías ver output similar a:
```
Migration table created successfully.
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table (XX ms)
...
```

#### 6.3 Ejecutar Seeders Necesarios

**⚠️ IMPORTANTE:** Solo ejecutar estos seeders en el orden indicado:

1. **SuperAdminSeeder** (Crear usuario Super Admin)
```bash
php artisan db:seed --class=SuperAdminSeeder --force
```

2. **ModeradorRoleSeeder** (Crear rol Moderador)
```bash
php artisan db:seed --class=ModeradorRoleSeeder --force
```

3. **CrLocationSeeder** (Crear ubicaciones de Costa Rica)
```bash
php artisan db:seed --class=CrLocationSeeder --force
```

4. **CategorySeeder** (Crear categorías de productos)
```bash
php artisan db:seed --class=CategorySeeder --force
```

**Checklist de seeders:**
- [ ] SuperAdminSeeder ejecutado ✅
- [ ] ModeradorRoleSeeder ejecutado ✅
- [ ] CrLocationSeeder ejecutado ✅
- [ ] CategorySeeder ejecutado ✅

**❌ NO EJECUTAR:**
- ProductSeeder (productos se agregarán manualmente)
- OrderSeeder (órdenes se crearán desde el frontend)

---

### FASE 7: Configurar CORS para Netlify

#### 7.1 Obtener URL de Netlify
- [ ] Desplegar tu frontend en Netlify
- [ ] Copiar la URL generada (ej: `https://tab-frontend-staging.netlify.app`)

#### 7.2 Actualizar FRONTEND_URL
- [ ] Ir a Settings → General → Custom environment variables
- [ ] Actualizar `FRONTEND_URL` con la URL real de Netlify

#### 7.3 Verificar CORS en config/cors.php
El archivo `config/cors.php` ya está configurado con:
```php
'allowed_origins' => [
    // Desarrollo local
    'http://localhost:8081',
    'http://localhost:3000',
    'http://localhost:5173',

    // Netlify - dominios por defecto
    'https://*.netlify.app',

    // Staging - dominio final (descomentar cuando esté listo)
    // 'https://toysandbricks.store',
    // 'https://www.toysandbricks.store',
],
```

**✅ El patrón `https://*.netlify.app` permite todos los subdominios de Netlify.**

#### 7.4 Redesplegar si es necesario
- [ ] Si modificaste `FRONTEND_URL`, redesplegar la aplicación

---

### FASE 8: Configurar Dominio Personalizado (DESPUÉS de pruebas)

**⚠️ SOLO REALIZAR DESPUÉS DE VERIFICAR QUE TODO FUNCIONA CON NETLIFY**

#### 8.1 Descomentar Dominio en CORS
- [ ] Editar `config/cors.php`
- [ ] Descomentar las líneas:
```php
'https://toysandbricks.store',
'https://www.toysandbricks.store',
```

#### 8.2 Actualizar FRONTEND_URL
- [ ] Actualizar variable de entorno: `FRONTEND_URL=https://toysandbricks.store`

#### 8.3 Configurar Dominio en Laravel Cloud
- [ ] Ir a Settings → Network → Domains
- [ ] Click "Add custom domain"
- [ ] Seguir las instrucciones para configurar DNS

#### 8.4 Commit y Push de Cambios
```bash
git add config/cors.php
git commit -m "Enable production domain in CORS configuration"
git push origin staging
```

#### 8.5 Redesplegar
- [ ] Click "Deploy" en Laravel Cloud

---

### FASE 9: Verificaciones Finales

#### 9.1 Verificar API
- [ ] Probar endpoint de salud: `GET https://tab-backend-v3-staging-jmuzni.laravel.cloud/api/health`
- [ ] Debería responder con código 200

#### 9.2 Verificar Autenticación
- [ ] Probar login del Super Admin creado por el seeder
- [ ] Verificar que se pueda obtener un token

#### 9.3 Verificar Object Storage
- [ ] Desde el frontend/Postman, intentar subir una imagen de producto
- [ ] Verificar que la imagen se suba correctamente al bucket R2
- [ ] Verificar que la URL devuelta sea accesible públicamente

#### 9.4 Verificar Email (Hostinger)
- [ ] Probar funcionalidad de "Recuperar contraseña"
- [ ] Verificar que llegue el email a la bandeja de entrada
- [ ] Verificar que el link de reset funcione correctamente

#### 9.5 Verificar CORS
- [ ] Desde el frontend en Netlify, hacer una petición a la API
- [ ] Verificar que NO haya errores de CORS en la consola del navegador

#### 9.6 Verificar Base de Datos
- [ ] Ejecutar comando de verificación:
```bash
php artisan db:show
```
- [ ] Verificar que muestre la conexión a PostgreSQL

---

## 🔧 Comandos Útiles de Laravel Cloud

### Ver Logs en Tiempo Real
```bash
# En Commands tab
php artisan pail
```

### Limpiar Caché
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Verificar Estado de la Aplicación
```bash
php artisan about
```

### Verificar Conexión a Base de Datos
```bash
php artisan db:show
```

### Verificar Almacenamiento
```bash
php artisan storage:link
```

---

## 📦 Estructura de Archivos Modificados

### Archivos Actualizados para Laravel Cloud:
```
Backend-Api-TAB-v3/
├── .env.example                          # ✅ Template completo para staging
├── config/
│   ├── filesystems.php                   # ✅ Configuración S3 para Object Storage
│   ├── cors.php                          # ✅ Dominios Netlify y staging
│   └── app.php                           # ✅ Ya tiene frontend_url configurado
└── app/
    └── Services/
        └── ProductImageService.php       # ✅ Soporte multi-disk (local/S3)
```

---

## 🌐 URLs y Endpoints Importantes

### Backend (Laravel Cloud)
- **API Base URL:** `https://tab-backend-v3-staging-jmuzni.laravel.cloud`
- **API Endpoints:** `https://tab-backend-v3-staging-jmuzni.laravel.cloud/api/v1/*`

### Frontend (Netlify)
- **Staging:** `https://your-app.netlify.app` (temporal)
- **Producción:** `https://toysandbricks.store` (configurar después)

### Object Storage (Cloudflare R2)
- **Bucket URL:** Inyectado automáticamente en `AWS_URL`
- **Acceso público:** Las imágenes serán accesibles vía URL pública del bucket

---

## 🔐 Seguridad - Checklist

- [ ] `APP_DEBUG=false` en staging/producción
- [ ] `APP_ENV=staging` (no "local")
- [ ] Variables sensibles en "Custom environment variables" (no en código)
- [ ] CORS configurado solo para dominios específicos (no `*`)
- [ ] Credenciales de email en variables de entorno
- [ ] Bucket configurado con visibilidad pública solo para imágenes
- [ ] HTTPS habilitado (automático en Laravel Cloud)

---

## 📝 Notas Importantes

### Sobre Object Storage:
1. **Laravel Cloud inyecta automáticamente las credenciales AWS** cuando adjuntas un bucket
2. **FILESYSTEM_DISK se establece automáticamente como `s3`** al adjuntar un bucket
3. **El disk debe llamarse exactamente `s3`** para que coincida con la configuración en `config/filesystems.php`
4. **Debes redesplegar después de adjuntar el bucket** para que los cambios surtan efecto

### Sobre la Base de Datos:
1. **Laravel Cloud inyecta automáticamente las credenciales de PostgreSQL** cuando adjuntas una base de datos
2. **NO necesitas configurar manualmente** `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
3. **Las variables se inyectan automáticamente** y están disponibles en el entorno

### Sobre CORS:
1. **El patrón `https://*.netlify.app`** permite todos los subdominios de Netlify
2. **Para producción**, descomentar `https://toysandbricks.store` después de las pruebas
3. **Laravel Cloud también gestiona CORS del bucket** automáticamente

### Sobre Emails:
1. **Hostinger SMTP ya está configurado** y funcionando
2. **Asegurar que `MAIL_PASSWORD`** sea la contraseña correcta en las variables de entorno
3. **Probar envío de emails** con "Recuperar contraseña" antes de ir a producción

---

## 🐛 Troubleshooting

### Problema: Imágenes no se suben al bucket
**Solución:**
1. Verificar que `FILESYSTEM_DISK=s3` en variables de entorno
2. Verificar que el bucket esté adjuntado al environment
3. Redesplegar la aplicación después de adjuntar el bucket

### Problema: Error de CORS desde Netlify
**Solución:**
1. Verificar que el dominio de Netlify coincida con el patrón `https://*.netlify.app`
2. Verificar que `FRONTEND_URL` esté correctamente configurado
3. Limpiar caché: `php artisan config:clear`

### Problema: Emails no se envían
**Solución:**
1. Verificar credenciales de Hostinger en variables de entorno
2. Probar conexión SMTP con: `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));`
3. Verificar logs: `php artisan pail`

### Problema: Base de datos no se conecta
**Solución:**
1. Verificar que la base de datos esté adjuntada al environment
2. Verificar variables inyectadas en Settings → General
3. Ejecutar: `php artisan db:show` para ver estado de conexión

---

## ✅ Checklist Final

- [ ] Repositorio actualizado y pusheado a `staging`
- [ ] Aplicación creada en Laravel Cloud
- [ ] Base de datos PostgreSQL adjuntada y configurada
- [ ] Bucket S3/R2 adjuntado y configurado
- [ ] Variables de entorno configuradas
- [ ] Migraciones ejecutadas
- [ ] Seeders necesarios ejecutados
- [ ] CORS configurado para Netlify
- [ ] Emails de Hostinger funcionando
- [ ] API funcionando correctamente
- [ ] Frontend en Netlify conectado al backend
- [ ] Subida de imágenes funcionando
- [ ] Autenticación funcionando

---

## 🎯 Próximos Pasos Después del Despliegue

1. **Probar todas las funcionalidades** desde el frontend en Netlify
2. **Verificar que los emails lleguen** correctamente
3. **Probar subida de imágenes** de productos
4. **Realizar órdenes de prueba** para verificar todo el flujo
5. **Una vez confirmado que todo funciona:**
   - Configurar dominio personalizado `toysandbricks.store`
   - Actualizar CORS con dominio final
   - Configurar DNS en Netlify para el frontend

---

**Fecha de creación:** 2025-12-05
**Ambiente:** Staging
**Versión:** 1.0
