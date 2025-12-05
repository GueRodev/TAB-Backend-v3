# 📦 Configuración de Bucket S3/R2 en Laravel Cloud

> Guía rápida para configurar Object Storage correctamente

---

## ⚠️ Errores Comunes a Evitar

1. ❌ Crear bucket con visibility "Private"
2. ❌ No configurar el disk name como "s3"
3. ❌ Olvidar configurar "Allowed origins"
4. ❌ No redesplegar después de adjuntar el bucket

---

## ✅ Configuración Correcta Paso a Paso

### **1. Crear el Bucket**

**Environment → Add resource → Bucket → Laravel Object Storage**

Configurar exactamente así:

```
Bucket type: Laravel Object Storage
Bucket name: [nombre-descriptivo]  (ej: tab_backend_v3)
Disk name: s3                       ⚠️ IMPORTANTE: Exactamente "s3"
Visibility: Public                  ⚠️ IMPORTANTE: Debe ser Public
```

✅ Click "Create bucket"

---

### **2. Configurar CORS (Allowed Origins)**

Después de crear el bucket:

**Resources → Object storage → [tu-bucket] → Edit settings**

En "Allowed origins", agregar (uno por línea):

```
https://tu-dominio.netlify.app
http://localhost:8081
http://localhost:3000
http://localhost:5173
```

✅ Save changes

---

### **3. Redesplegar la Aplicación**

**⚠️ CRÍTICO: Sin redesplegar, los cambios NO surten efecto**

**Dashboard → Deploy**

Esperar a que el deployment complete (~1-2 minutos)

---

### **4. Actualizar Variables de Entorno**

**Settings → General → Custom environment variables**

Agregar/verificar:

```env
FRONTEND_URL=https://tu-dominio.netlify.app
```

No necesitas agregar variables AWS_* - Laravel Cloud las inyecta automáticamente.

---

### **5. Verificar Configuración**

**Settings → General → Injected variables**

Deberías ver:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_BUCKET=xxx
AWS_ENDPOINT=https://xxx.r2.cloudflarestorage.com
AWS_URL=https://xxx.r2.cloudflarestorage.com/xxx
```

---

## 🧪 Probar que Funciona

### **1. Crear un producto con imagen desde el frontend**

La imagen debería subirse correctamente.

### **2. Inspeccionar la URL de la imagen**

Click derecho → Inspeccionar elemento

Debería ver:
```html
<img src="https://xxx.r2.cloudflarestorage.com/products/123.jpg">
```

### **3. Abrir la URL en nueva pestaña**

La imagen debería cargar correctamente.

---

## 🔧 Troubleshooting

### **Problema: Imagen no se sube**

**Causa:** Bucket en "Private"

**Solución:**
1. Resources → Object storage → tu bucket → Edit settings
2. Cambiar Visibility a "Public"
3. Save
4. Redesplegar

---

### **Problema: Imagen se sube pero no se ve**

**Causa:** CORS no configurado o deployment pendiente

**Solución:**
1. Verificar "Allowed origins" incluye tu dominio de Netlify
2. Redesplegar la aplicación
3. Limpiar caché: `php artisan config:clear`

---

### **Problema: Error "disk s3 not found"**

**Causa:** Disk name incorrecto al crear el bucket

**Solución:**
1. Eliminar bucket
2. Crear nuevo con disk name exactamente "s3"
3. Redesplegar

---

## 📝 Checklist Final

Antes de dar por terminado:

- [ ] Bucket creado con visibility "Public"
- [ ] Disk name configurado como "s3"
- [ ] Allowed origins incluye dominio de Netlify + localhost
- [ ] Variables de entorno inyectadas correctamente (AWS_*)
- [ ] Deployment realizado después de cambios
- [ ] Imagen de prueba se ve correctamente en el frontend

---

## 🎯 Resultado Esperado

- ✅ Imágenes se suben al bucket R2
- ✅ URLs apuntan a `https://xxx.r2.cloudflarestorage.com/products/`
- ✅ Imágenes cargables desde el frontend
- ✅ CORS funcionando correctamente

---

**Fecha:** 2025-12-05
**Versión:** 1.0
