# 🌐 Configurar Dominio Personalizado: toysandbricks.store

> Guía para conectar tu dominio de Hostinger con Netlify y actualizar Laravel Cloud

---

## 📋 Información Importante

- **Dominio:** toysandbricks.store
- **Registrador:** Hostinger
- **Frontend:** Netlify
- **Backend:** Laravel Cloud
- **SSL:** ✅ Automático (gratis) por Netlify (Let's Encrypt)

---

## ✅ Opción Recomendada: Mantener DNS en Hostinger

**Ventajas:**
- ✅ Más simple
- ✅ Mantiene email en Hostinger funcionando
- ✅ Solo requiere agregar 2 registros DNS

---

## 🚀 Paso a Paso Completo

### **PARTE 1: Configurar en Netlify**

#### **1.1 Agregar Dominio en Netlify**

1. Ve a tu sitio en Netlify
2. **Site configuration** → **Domain management**
3. Click **"Add a domain"**
4. Selecciona **"Add a domain you already own"**
5. Escribe: `toysandbricks.store`
6. Click **"Verify"** → **"Add domain"**

Netlify te mostrará:
```
⚠️ DNS configuration needed
```

---

#### **1.2 Agregar www (Opcional pero Recomendado)**

1. En la misma sección, click **"Add domain alias"**
2. Escribe: `www.toysandbricks.store`
3. Click **"Add domain"**

---

### **PARTE 2: Configurar DNS en Hostinger**

#### **2.1 Acceder a DNS de Hostinger**

1. Inicia sesión en [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Ve a **Dominios** → **toysandbricks.store**
3. Click en **DNS / Name Servers**
4. Busca la sección **DNS Records**

---

#### **2.2 Agregar Registros DNS**

**Elimina registros A existentes del dominio principal (si existen)**

Luego agrega estos 2 registros:

##### **Registro 1: Apex Domain (toysandbricks.store)**

```
Type: A
Name: @ (o dejar vacío)
Points to: 75.2.60.5
TTL: 3600 (o default)
```

##### **Registro 2: Subdomain www**

```
Type: CNAME
Name: www
Points to: toysandbricks-store.netlify.app
TTL: 3600 (o default)
```

⚠️ **Importante:** Usa el nombre exacto de tu sitio en Netlify (`toysandbricks-store.netlify.app`)

---

#### **2.3 Guardar Cambios**

Click **"Add Record"** o **"Save"** para cada uno.

---

### **PARTE 3: Esperar Propagación DNS**

**Tiempo:** 5 minutos - 48 horas (usualmente 15-30 min)

#### **Verificar Propagación:**

Usa esta herramienta: [dnschecker.org](https://dnschecker.org)

Escribe: `toysandbricks.store`

Debería mostrar: `75.2.60.5` en múltiples ubicaciones.

---

### **PARTE 4: SSL Automático en Netlify**

**Netlify provisionará automáticamente un certificado SSL gratuito (Let's Encrypt)**

**Cómo verificar:**

1. En Netlify → **Domain management** → **HTTPS**
2. Deberías ver:
   ```
   ✅ Certificate status: Active
   ✅ HTTPS enabled
   ```

**Tiempo:** 1-2 horas después de propagación DNS

⚠️ **Si no aparece después de 24 horas:**
- Verifica que los registros DNS estén correctos
- En Netlify, click **"Verify DNS configuration"**
- Contact Netlify support si persiste

---

### **PARTE 5: Actualizar Laravel Cloud (Backend)**

#### **5.1 Actualizar FRONTEND_URL**

**Laravel Cloud → Settings → General → Custom environment variables**

Cambiar:
```env
FRONTEND_URL=https://toysandbricks-store.netlify.app
```

A:
```env
FRONTEND_URL=https://toysandbricks.store
```

✅ Save

---

#### **5.2 Actualizar Bucket CORS**

**Laravel Cloud → Resources → Object storage → tab_backend_v3 → Edit settings**

En **"Allowed origins"**, agregar:

```
https://toysandbricks.store
https://www.toysandbricks.store
https://toysandbricks-store.netlify.app
http://localhost:8081
http://localhost:3000
http://localhost:5173
```

✅ Save

---

#### **5.3 Actualizar config/cors.php (código)**

Editar `Backend-Api-TAB-v3/config/cors.php`:

Descomentar las líneas del dominio final:

```php
'allowed_origins' => [
    // Desarrollo local
    'http://localhost:8081',
    'http://localhost:3000',
    'http://localhost:5173',

    // Netlify
    'https://*.netlify.app',

    // Staging/Producción - dominio final
    'https://toysandbricks.store',
    'https://www.toysandbricks.store',
],
```

---

#### **5.4 Commit y Push**

```bash
git add config/cors.php
git commit -m "Enable production domain in CORS configuration"
git push origin staging
```

Laravel Cloud desplegará automáticamente.

---

### **PARTE 6: Verificar que Todo Funciona**

#### **6.1 Probar el Dominio**

Abre en el navegador:
```
https://toysandbricks.store
```

Deberías ver:
- ✅ Tu frontend cargando
- ✅ Candado SSL en la barra de direcciones
- ✅ Sin errores de certificado

---

#### **6.2 Probar Conexión con Backend**

1. Login con Super Admin
2. Crear un producto con imagen
3. Verificar que:
   - ✅ Se crea correctamente
   - ✅ La imagen se ve
   - ✅ No hay errores de CORS en consola (F12)

---

#### **6.3 Verificar SSL**

Click en el candado 🔒 en la barra del navegador:
- ✅ Connection is secure
- ✅ Certificate issued by: Let's Encrypt
- ✅ Valid from/to dates

---

## 🔧 Troubleshooting

### **Problema: "DNS_PROBE_FINISHED_NXDOMAIN"**

**Causa:** DNS no propagado o registros incorrectos

**Solución:**
1. Verificar registros DNS en Hostinger
2. Esperar más tiempo (hasta 48h)
3. Usar [dnschecker.org](https://dnschecker.org)

---

### **Problema: "Certificate error" o "Not secure"**

**Causa:** SSL no provisionado aún

**Solución:**
1. Esperar 1-2 horas después de DNS propagado
2. En Netlify → HTTPS → "Renew certificate"
3. Verificar que DNS esté correcto

---

### **Problema: Frontend carga pero CORS errors**

**Causa:** CORS no actualizado en backend

**Solución:**
1. Verificar `FRONTEND_URL` en Laravel Cloud
2. Verificar bucket "Allowed origins"
3. Verificar `config/cors.php` incluye el dominio
4. Redesplegar Laravel Cloud

---

### **Problema: "www" no funciona pero dominio principal sí**

**Causa:** Falta registro CNAME para www

**Solución:**
1. Agregar registro CNAME en Hostinger:
   ```
   Type: CNAME
   Name: www
   Points to: toysandbricks-store.netlify.app
   ```

---

## 📋 Checklist Final

Antes de dar por terminado:

- [ ] Dominio agregado en Netlify
- [ ] Registro A apuntando a 75.2.60.5 en Hostinger
- [ ] Registro CNAME para www en Hostinger
- [ ] DNS propagado (verificado en dnschecker.org)
- [ ] SSL activo (candado verde en navegador)
- [ ] FRONTEND_URL actualizada en Laravel Cloud
- [ ] Bucket CORS actualizado con nuevo dominio
- [ ] config/cors.php actualizado y desplegado
- [ ] Frontend accesible en https://toysandbricks.store
- [ ] Backend conectado sin errores de CORS
- [ ] Imágenes cargando correctamente

---

## 🎯 URLs Finales

**Frontend:**
```
https://toysandbricks.store
https://www.toysandbricks.store
```

**Backend API:**
```
https://tab-backend-v3-staging-jmuzni.laravel.cloud
```

**Backup (Netlify default):**
```
https://toysandbricks-store.netlify.app
```

---

## 📚 Referencias

- [Netlify Custom Domains](https://docs.netlify.com/domains-https/custom-domains/)
- [Netlify SSL/HTTPS](https://docs.netlify.com/domains-https/https-ssl/)
- [Configure External DNS for Netlify](https://docs.netlify.com/manage/domains/configure-domains/configure-external-dns/)
- [DNS Checker](https://dnschecker.org)

---

**Fecha:** 2025-12-06
**Versión:** 1.0
