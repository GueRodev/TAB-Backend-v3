-- ========================================================================
-- QUERIES DE VERIFICACIÓN DE PEDIDOS
-- Ejecutar estas queries en tu base de datos para verificar el estado real
-- ========================================================================

-- 1. Contar TODOS los pedidos (sin soft deletes)
SELECT COUNT(*) as total_pedidos_activos
FROM orders
WHERE deleted_at IS NULL;

-- 2. Contar pedidos por estado (sin soft deletes)
SELECT
    status,
    COUNT(*) as cantidad
FROM orders
WHERE deleted_at IS NULL
GROUP BY status
ORDER BY status;

-- 3. Contar pedidos INCLUYENDO los eliminados (soft deleted)
SELECT COUNT(*) as total_pedidos_todos
FROM orders;

-- 4. Contar pedidos eliminados vs activos
SELECT
    CASE
        WHEN deleted_at IS NULL THEN 'Activo'
        ELSE 'Eliminado'
    END as estado_registro,
    COUNT(*) as cantidad
FROM orders
GROUP BY estado_registro;

-- 5. Ver todos los pedidos con sus fechas relevantes (últimos 100)
SELECT
    id,
    order_number,
    status,
    order_type,
    created_at,
    completed_at,
    cancelled_at,
    deleted_at
FROM orders
ORDER BY created_at DESC
LIMIT 100;

-- 6. Pedidos en el rango de fecha que estás usando (03 nov - 03 dic 2025)
SELECT
    COUNT(*) as pedidos_creados_en_rango,
    status
FROM orders
WHERE created_at BETWEEN '2025-11-03 00:00:00' AND '2025-12-03 23:59:59'
  AND deleted_at IS NULL
GROUP BY status;

-- 7. Pedidos COMPLETADOS en el rango (por fecha de completado)
SELECT
    COUNT(*) as pedidos_completados_en_rango
FROM orders
WHERE status = 'completed'
  AND completed_at BETWEEN '2025-11-03 00:00:00' AND '2025-12-03 23:59:59'
  AND deleted_at IS NULL;

-- 8. Pedidos CANCELADOS en el rango (por fecha de cancelación)
SELECT
    COUNT(*) as pedidos_cancelados_en_rango
FROM orders
WHERE status = 'cancelled'
  AND cancelled_at BETWEEN '2025-11-03 00:00:00' AND '2025-12-03 23:59:59'
  AND deleted_at IS NULL;

-- 9. Pedidos CREADOS en el rango (sin importar estado actual)
SELECT
    COUNT(*) as pedidos_creados_en_rango
FROM orders
WHERE created_at BETWEEN '2025-11-03 00:00:00' AND '2025-12-03 23:59:59'
  AND deleted_at IS NULL;

-- 10. Desglose completo por estado en el rango de fecha
SELECT
    status,
    COUNT(*) as cantidad,
    MIN(created_at) as primer_pedido,
    MAX(created_at) as ultimo_pedido
FROM orders
WHERE created_at BETWEEN '2025-11-03 00:00:00' AND '2025-12-03 23:59:59'
  AND deleted_at IS NULL
GROUP BY status
ORDER BY status;

-- 11. Ver pedidos completados con sus fechas de creación vs completado
SELECT
    id,
    order_number,
    customer_name,
    status,
    total,
    created_at,
    completed_at,
    TIMESTAMPDIFF(HOUR, created_at, completed_at) as horas_para_completar
FROM orders
WHERE status = 'completed'
  AND deleted_at IS NULL
ORDER BY completed_at DESC
LIMIT 50;

-- 12. Ver pedidos cancelados con sus fechas
SELECT
    id,
    order_number,
    customer_name,
    status,
    total,
    created_at,
    cancelled_at,
    TIMESTAMPDIFF(HOUR, created_at, cancelled_at) as horas_para_cancelar
FROM orders
WHERE status = 'cancelled'
  AND deleted_at IS NULL
ORDER BY cancelled_at DESC
LIMIT 50;
