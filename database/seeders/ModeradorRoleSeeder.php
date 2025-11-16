<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ModeradorRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * SEEDER SEGURO: Solo crea el rol Moderador si NO existe.
     * Puede ejecutarse múltiples veces sin causar errores.
     */
    public function run(): void
    {
        // Limpiar cache de roles y permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Verificar si el rol Moderador ya existe
        $moderador = Role::firstOrCreate(['name' => 'Moderador']);

        // Definir permisos del Moderador
        $permisosModerador = [
            // Productos (ver y editar, NO crear/eliminar)
            'view products',
            'edit products',

            // Categorías (solo ver, NO crear/editar/eliminar)
            'view categories',

            // Pedidos (TODAS las operaciones EXCEPTO eliminar)
            'view all orders',
            'create store orders',     // SÍ puede crear pedidos en tienda física
            'update order status',
            'cancel orders',            // SÍ puede cancelar (NO eliminar para auditoría)

            // Direcciones (ver todas para soporte al cliente)
            'view all addresses',

            // Perfil propio
            'view own profile',
            'edit own profile',

            // Dashboard y reportes
            'view dashboard',
            'view order reports',
        ];

        // Sincronizar permisos (agrega nuevos, mantiene existentes)
        $moderador->syncPermissions($permisosModerador);

        // Log de éxito
        $this->command->info('✅ Rol Moderador creado/actualizado exitosamente');
        $this->command->info('✅ Permisos asignados: ' . $moderador->permissions->count());
    }
}
