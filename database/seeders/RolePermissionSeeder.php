<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
//use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar cache de roles y permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // ==================================================
        // CREAR PERMISOS POR MÓDULO
        // ==================================================
        
        // Permisos de Productos
        Permission::create(['name' => 'view products']);
        Permission::create(['name' => 'create products']);
        Permission::create(['name' => 'edit products']);
        Permission::create(['name' => 'delete products']);
        
        // Permisos de Categorías
        Permission::create(['name' => 'view categories']);
        Permission::create(['name' => 'create categories']);
        Permission::create(['name' => 'edit categories']);
        Permission::create(['name' => 'delete categories']);
        
        // Permisos de Pedidos
        Permission::create(['name' => 'view all orders']);         // Ver TODOS los pedidos (admin)
        Permission::create(['name' => 'view own orders']);         // Ver solo sus propios pedidos (cliente)
        Permission::create(['name' => 'create cart orders']);      // Crear pedidos desde carrito online (cliente)
        Permission::create(['name' => 'create store orders']);     // Crear pedidos en tienda física (admin)
        Permission::create(['name' => 'update order status']);     // Cambiar estado del pedido (admin)
        Permission::create(['name' => 'cancel orders']);           // Cancelar pedidos (admin)
        Permission::create(['name' => 'delete orders']);           // Eliminar pedidos (admin)
        
        // Permisos de Usuarios
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);
        
        // Permisos de Perfil
        Permission::create(['name' => 'view own profile']);
        Permission::create(['name' => 'edit own profile']);
        
        // Permisos de Direcciones
        Permission::create(['name' => 'view all addresses']);      // Ver todas las direcciones (admin)
        Permission::create(['name' => 'manage own addresses']);    // Gestionar sus propias direcciones (cliente)
        
        // Permisos de Carrito
        Permission::create(['name' => 'manage own cart']);
        
        // Permisos de Favoritos
        Permission::create(['name' => 'add to favorites']);
        Permission::create(['name' => 'remove from favorites']);
        Permission::create(['name' => 'view own favorites']);
        
        // Permisos de Dashboard y Reportes
        Permission::create(['name' => 'view dashboard']);
        Permission::create(['name' => 'view order reports']);
        
        // ==================================================
        // CREAR ROLES
        // ==================================================
        
        // Rol: Super Admin
        $superAdmin = Role::create(['name' => 'Super Admin']);
        
        // Rol: Cliente
        $cliente = Role::create(['name' => 'Cliente']);
        
        // ==================================================
        // ASIGNAR PERMISOS A ROLES
        // ==================================================
        
        // Super Admin - Todos los permisos
        $superAdmin->givePermissionTo(Permission::all());
        
        // Cliente - Permisos limitados
        $cliente->givePermissionTo([
            // Productos (solo ver)
            'view products',
            'view categories',
            
            // Pedidos (crear desde carrito y ver los suyos)
            'view own orders',
            'create cart orders',
            
            // Perfil y datos personales
            'view own profile',
            'edit own profile',
            'manage own addresses',
            'manage own cart',
            
            // Favoritos
            'add to favorites',
            'remove from favorites',
            'view own favorites',
        ]);
        
        // Log de éxito
        $this->command->info('✅ Roles y permisos creados exitosamente');
        $this->command->info('✅ Super Admin: ' . $superAdmin->permissions->count() . ' permisos asignados');
        $this->command->info('✅ Cliente: ' . $cliente->permissions->count() . ' permisos asignados');
    }
}