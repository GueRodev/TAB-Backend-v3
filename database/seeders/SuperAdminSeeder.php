<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe un Super Admin
        $existingSuperAdmin = User::role('Super Admin')->first();

        if ($existingSuperAdmin) {
            $this->command->warn('⚠️  Ya existe un usuario Super Admin en el sistema');
            $this->command->info('📧 Email: ' . $existingSuperAdmin->email);
            return;
        }

        // Crear usuario Super Admin
        $superAdmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'toysandbricksdev@gmail.com',
            'password' => Hash::make('password'), // ⚠️ CAMBIAR EN PRODUCCIÓN
            'email_verified_at' => now(),
        ]);

        // Asignar rol de Super Admin
        $superAdmin->assignRole('Super Admin');

        // Log de éxito
        $this->command->info('✅ Usuario Super Admin creado exitosamente');
        $this->command->info('📧 Email: toysandbricksdev@gmail.com');
        $this->command->warn('🔒 Password: password (CAMBIAR EN PRODUCCIÓN)');
        $this->command->info('🎭 Rol: Super Admin');
        $this->command->info('🔑 Permisos: ' . $superAdmin->getAllPermissions()->count() . ' permisos asignados');
    }
}