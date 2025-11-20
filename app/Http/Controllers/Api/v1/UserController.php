<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StoreUserRequest;
use App\Http\Requests\v1\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Controlador para gestión de usuarios (Super Admin y Moderador)
 *
 * REGLAS DE NEGOCIO:
 * - Solo Super Admin puede acceder a estos endpoints
 * - Solo gestiona usuarios Admin y Moderador (NO Cliente)
 * - Los clientes se gestionan mediante registro público
 * - No se puede eliminar el último Super Admin del sistema
 * - Un usuario no puede eliminarse a sí mismo
 */
class UserController extends Controller
{
    /**
     * Listar todos los usuarios Admin y Moderador
     *
     * GET /api/v1/users
     *
     * FILTRADO:
     * - Solo muestra usuarios con rol "Super Admin" o "Moderador"
     * - NO muestra usuarios con rol "Cliente"
     * - Incluye el rol de cada usuario en la respuesta
     *
     * @return JsonResponse Lista de usuarios con sus roles
     */
    public function index(): JsonResponse
    {
        try {
            // Obtener usuarios que tengan rol Super Admin o Moderador
            // whereHas filtra solo usuarios que cumplan la condición del rol
            $users = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'Moderador']);
            })
            ->with('roles:id,name') // Cargar relación de roles (solo id y nombre)
            ->orderBy('created_at', 'desc') // Ordenar por más recientes primero
            ->get();

            // Formatear respuesta: agregar campo 'role' con el nombre del primer rol
            $users->each(function ($user) {
                $user->role = $user->getRoleNames()->first(); // Obtener nombre del rol
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users
                ]
            ], 200);

        } catch (Exception $e) {
            // Log del error para debugging
            Log::error('Error al listar usuarios: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de usuarios',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Crear nuevo usuario Admin o Moderador
     *
     * POST /api/v1/users
     *
     * PROCESO:
     * 1. Validar datos (via StoreUserRequest)
     * 2. Hashear password
     * 3. Crear usuario en base de datos
     * 4. Asignar rol usando Spatie Permissions
     * 5. Retornar usuario creado con su rol
     *
     * USO DE DB TRANSACTION:
     * - Crear usuario + Asignar rol deben ser ATÓMICAS
     * - Si falla asignar rol, se hace rollback y NO queda usuario sin rol
     *
     * @param StoreUserRequest $request Datos validados del nuevo usuario
     * @return JsonResponse Usuario creado con código 201
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            // Obtener datos validados del request
            $data = $request->validated();

            // SEGURIDAD: Hashear password antes de guardar
            $data['password'] = Hash::make($data['password']);

            // Extraer el rol para asignarlo después (no es campo de la tabla users)
            $role = $data['role'];
            unset($data['role']); // Remover del array para evitar error en create()

            // TRANSACCIÓN: Crear usuario + Asignar rol (atómico)
            $user = DB::transaction(function () use ($data, $role) {
                // Crear usuario en base de datos
                $user = User::create($data);

                // IMPORTANTE: Asignar rol usando Spatie Permissions
                // Si esto falla, se hace rollback automático del create()
                $user->assignRole($role);

                return $user;
            });

            // Cargar relación de roles para incluir en respuesta
            $user->load('roles:id,name');
            $user->role = $user->getRoleNames()->first();

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => [
                    'user' => $user
                ]
            ], 201); // Código 201 = Created

        } catch (Exception $e) {
            // Log del error con contexto
            Log::error('Error al crear usuario: ' . $e->getMessage(), [
                'email' => $request->email ?? null,
                'role' => $request->role ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un usuario específico
     *
     * GET /api/v1/users/{id}
     *
     * @param int $id ID del usuario a mostrar
     * @return JsonResponse Datos del usuario con su rol
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Buscar usuario o retornar 404 si no existe
            $user = User::findOrFail($id);

            // VALIDACIÓN: Verificar que sea Admin o Moderador
            if (!$user->hasAnyRole(['Super Admin', 'Moderador'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden consultar usuarios Admin o Moderador'
                ], 403);
            }

            // Cargar roles del usuario
            $user->load('roles:id,name');
            $user->role = $user->getRoleNames()->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error al mostrar usuario: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos del usuario',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Actualizar usuario existente
     *
     * PUT/PATCH /api/v1/users/{id}
     *
     * PROCESO:
     * 1. Validar datos (via UpdateUserRequest - incluye validaciones de negocio)
     * 2. Preparar datos (hashear password si existe, extraer rol)
     * 3. Actualizar usuario + rol en transacción (atómico)
     * 4. Retornar usuario actualizado
     *
     * USO DE DB TRANSACTION:
     * - Actualizar datos + Cambiar rol deben ser ATÓMICAS
     * - Si falla cambiar rol, se hace rollback de la actualización
     *
     * NOTA: Las validaciones de negocio (no cambiar propio rol, no cambiar
     * último Super Admin) ya están en UpdateUserRequest
     *
     * @param UpdateUserRequest $request Datos validados
     * @param int $id ID del usuario a actualizar
     * @return JsonResponse Usuario actualizado
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            // Buscar usuario o retornar 404
            $user = User::findOrFail($id);

            // VALIDACIÓN: Solo permitir actualizar Admin o Moderador
            if (!$user->hasAnyRole(['Super Admin', 'Moderador'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden actualizar usuarios Admin o Moderador'
                ], 403);
            }

            // Obtener datos validados
            $data = $request->validated();

            // PREPARAR DATOS: Hashear password si existe
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']); // No actualizar password si está vacío
            }

            // PREPARAR DATOS: Extraer rol (se maneja por separado)
            $newRole = $data['role'] ?? null;
            unset($data['role']);

            // TRANSACCIÓN: Actualizar usuario + Cambiar rol (atómico)
            DB::transaction(function () use ($user, $data, $newRole) {
                // Actualizar campos básicos del usuario (name, email, phone)
                if (!empty($data)) {
                    $user->update($data);
                }

                // Si se envió un nuevo rol, actualizarlo
                if ($newRole) {
                    // syncRoles() reemplaza TODOS los roles por el nuevo
                    $user->syncRoles([$newRole]);
                }
            });

            // Recargar usuario con sus roles actualizados
            $user->refresh(); // refresh() es más eficiente que fresh()
            $user->load('roles:id,name');
            $user->role = $user->getRoleNames()->first();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => [
                    'user' => $user
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error al actualizar usuario: ' . $e->getMessage(), [
                'user_id' => $id,
                'data' => $request->except('password'), // No loguear password
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Eliminar usuario
     *
     * DELETE /api/v1/users/{id}
     *
     * VALIDACIONES DE SEGURIDAD:
     * 1. Solo permitir eliminar usuarios Admin o Moderador
     * 2. No permitir que un usuario se elimine a sí mismo
     * 3. No permitir eliminar el último Super Admin (evita bloqueo del sistema)
     *
     * NO USA DB TRANSACTION porque:
     * - Es una sola operación de BD (delete)
     * - Spatie Permissions elimina automáticamente las relaciones en model_has_roles
     *
     * @param int $id ID del usuario a eliminar
     * @return JsonResponse Confirmación de eliminación
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Buscar usuario o retornar 404
            $user = User::findOrFail($id);

            // VALIDACIÓN 1: Solo permitir eliminar Admin o Moderador
            if (!$user->hasAnyRole(['Super Admin', 'Moderador'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar usuarios Admin o Moderador'
                ], 403);
            }

            // VALIDACIÓN 2: No permitir eliminarse a sí mismo
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta'
                ], 403);
            }

            // VALIDACIÓN 3: CRÍTICA - No eliminar el último Super Admin
            // Esto evita que el sistema quede sin administradores
            if ($this->isLastSuperAdmin($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el último Super Admin del sistema'
                ], 403);
            }

            // Guardar datos para el mensaje de confirmación
            $userName = $user->name;
            $userRole = $user->getRoleNames()->first();

            // Eliminar usuario
            // NOTA: Spatie Permissions elimina automáticamente
            // las relaciones en model_has_roles gracias al cascade
            $user->delete();

            // Log de auditoría
            Log::info('Usuario eliminado', [
                'deleted_user_id' => $id,
                'deleted_user_name' => $userName,
                'deleted_user_role' => $userRole,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Usuario '{$userName}' eliminado exitosamente"
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener permisos de un usuario específico
     *
     * GET /api/v1/users/{id}/permissions
     *
     * ENDPOINT EXCLUSIVO PARA CONSULTAR PERMISOS:
     * - Muestra todos los permisos del usuario (directos + heredados del rol)
     * - Solo puede consultar usuarios Admin o Moderador
     * - Retorna lista de nombres de permisos
     *
     * @param int $id ID del usuario
     * @return JsonResponse Lista de permisos del usuario
     */
    public function getPermissions(int $id): JsonResponse
    {
        try {
            // Buscar usuario o retornar 404 si no existe
            $user = User::findOrFail($id);

            // VALIDACIÓN: Verificar que sea Admin o Moderador
            if (!$user->hasAnyRole(['Super Admin', 'Moderador'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden consultar permisos de usuarios Admin o Moderador'
                ], 403);
            }

            // Obtener todos los permisos (directos + heredados del rol)
            $permissions = $user->getAllPermissions()->pluck('name');

            // Obtener el rol del usuario para contexto
            $role = $user->getRoleNames()->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role' => $role,
                    'permissions' => $permissions,
                    'permissions_count' => $permissions->count()
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error al obtener permisos del usuario: ' . $e->getMessage(), [
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los permisos del usuario',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verifica si el usuario es el último Super Admin del sistema
     *
     * LÓGICA:
     * - Si el usuario tiene rol Super Admin
     * - Y solo hay 1 Super Admin en total en el sistema
     * - Entonces es el último Super Admin
     *
     * @param User $user Usuario a verificar
     * @return bool True si es el último Super Admin
     */
    private function isLastSuperAdmin(User $user): bool
    {
        return $user->hasRole('Super Admin') &&
               User::role('Super Admin')->count() <= 1;
    }
}
