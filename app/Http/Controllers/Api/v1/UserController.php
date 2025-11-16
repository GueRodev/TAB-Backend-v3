<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StoreUserRequest;
use App\Http\Requests\v1\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

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
            'users' => $users
        ]);
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
     * @param StoreUserRequest $request Datos validados del nuevo usuario
     * @return JsonResponse Usuario creado con código 201
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Obtener datos validados del request
        $data = $request->validated();

        // SEGURIDAD: Hashear password antes de guardar
        $data['password'] = Hash::make($data['password']);

        // Extraer el rol para asignarlo después (no es campo de la tabla users)
        $role = $data['role'];
        unset($data['role']); // Remover del array para evitar error en create()

        // Crear usuario en base de datos
        $user = User::create($data);

        // IMPORTANTE: Asignar rol usando Spatie Permissions
        // assignRole() crea registro en model_has_roles
        $user->assignRole($role);

        // Cargar relación de roles para incluir en respuesta
        $user->load('roles:id,name');
        $user->role = $user->getRoleNames()->first();

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user
        ], 201); // Código 201 = Created
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
        // Buscar usuario o retornar 404 si no existe
        $user = User::findOrFail($id);

        // VALIDACIÓN: Verificar que sea Admin o Moderador
        // Si es Cliente, retornar error 403
        if (!$user->hasAnyRole(['Super Admin', 'Moderador'])) {
            return response()->json([
                'message' => 'Solo se pueden consultar usuarios Admin o Moderador'
            ], 403);
        }

        // Cargar roles del usuario
        $user->load('roles:id,name');
        $user->role = $user->getRoleNames()->first();

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Actualizar usuario existente
     *
     * PUT/PATCH /api/v1/users/{id}
     *
     * PROCESO:
     * 1. Validar datos (via UpdateUserRequest - incluye validaciones de negocio)
     * 2. Si hay password, hashearlo
     * 3. Actualizar datos básicos del usuario
     * 4. Si se cambió el rol, actualizarlo usando syncRoles()
     * 5. Retornar usuario actualizado
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
        // Buscar usuario o retornar 404
        $user = User::findOrFail($id);

        // VALIDACIÓN: Solo permitir actualizar Admin o Moderador
        if (!$user->hasAnyRole(['Super Admin', 'Moderador'])) {
            return response()->json([
                'message' => 'Solo se pueden actualizar usuarios Admin o Moderador'
            ], 403);
        }

        // Obtener datos validados
        $data = $request->validated();

        // Si se proporcionó password, hashearlo
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Si no hay password, no actualizar ese campo
            unset($data['password']);
        }

        // Extraer rol si existe (se maneja por separado)
        $newRole = null;
        if (isset($data['role'])) {
            $newRole = $data['role'];
            unset($data['role']); // Remover para evitar error en update()
        }

        // Actualizar campos básicos del usuario
        $user->update($data);

        // Si se envió un nuevo rol, actualizarlo
        if ($newRole) {
            // syncRoles() reemplaza TODOS los roles por el nuevo
            // (en este sistema cada usuario tiene solo 1 rol)
            $user->syncRoles([$newRole]);
        }

        // Recargar usuario con sus roles actualizados
        $user->load('roles:id,name');
        $user->role = $user->getRoleNames()->first();

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user->fresh() // fresh() recarga desde BD
        ]);
    }

    /**
     * Eliminar usuario
     *
     * DELETE /api/v1/users/{id}
     *
     * VALIDACIONES DE SEGURIDAD:
     * 1. No permitir eliminar el último Super Admin (evita bloqueo del sistema)
     * 2. No permitir que un usuario se elimine a sí mismo
     * 3. Solo permitir eliminar usuarios Admin o Moderador
     *
     * @param int $id ID del usuario a eliminar
     * @return JsonResponse Confirmación de eliminación
     */
    public function destroy(int $id): JsonResponse
    {
        // Buscar usuario o retornar 404
        $user = User::findOrFail($id);

        // VALIDACIÓN 1: Solo permitir eliminar Admin o Moderador
        if (!$user->hasAnyRole(['Super Admin', 'Moderador'])) {
            return response()->json([
                'message' => 'Solo se pueden eliminar usuarios Admin o Moderador'
            ], 403);
        }

        // VALIDACIÓN 2: No permitir eliminarse a sí mismo
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta'
            ], 403);
        }

        // VALIDACIÓN 3: CRÍTICA - No eliminar el último Super Admin
        // Esto evita que el sistema quede sin administradores
        if ($this->isLastSuperAdmin($user)) {
            return response()->json([
                'message' => 'No se puede eliminar el último Super Admin del sistema'
            ], 403);
        }

        // Guardar nombre para mensaje de confirmación
        $userName = $user->name;

        // Eliminar usuario (Spatie Permissions elimina automáticamente
        // las relaciones en model_has_roles)
        $user->delete();

        return response()->json([
            'message' => "Usuario '{$userName}' eliminado exitosamente"
        ]);
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
