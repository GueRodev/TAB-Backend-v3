<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CrLocation;

class CrLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Cargando ubicaciones de Costa Rica...');

        // Leer JSON
        $json = file_get_contents(base_path('docs/cr_shipping_data.json'));
        $data = json_decode($json, true);

        $totalProvincias = 0;
        $totalCantones = 0;
        $totalDistritos = 0;

        foreach ($data['provincias'] as $provincia) {
            // Insertar provincia
            CrLocation::create([
                'province_id' => $provincia['id'],
                'canton_id' => null,
                'province_name' => $provincia['nombre'],
                'canton_name' => null,
                'district_name' => null,
                'type' => 'province',
            ]);
            $totalProvincias++;

            foreach ($provincia['cantones'] as $canton) {
                // Insertar cantón
                CrLocation::create([
                    'province_id' => $provincia['id'],
                    'canton_id' => $canton['id'],
                    'province_name' => $provincia['nombre'],
                    'canton_name' => $canton['nombre'],
                    'district_name' => null,
                    'type' => 'canton',
                ]);
                $totalCantones++;

                foreach ($canton['distritos'] as $distrito) {
                    // Insertar distrito
                    CrLocation::create([
                        'province_id' => $provincia['id'],
                        'canton_id' => $canton['id'],
                        'province_name' => $provincia['nombre'],
                        'canton_name' => $canton['nombre'],
                        'district_name' => $distrito,
                        'type' => 'district',
                    ]);
                    $totalDistritos++;
                }
            }
        }

        $this->command->info("✅ {$totalProvincias} provincias cargadas");
        $this->command->info("✅ {$totalCantones} cantones cargados");
        $this->command->info("✅ {$totalDistritos} distritos cargados");
        $this->command->info('✅ Ubicaciones de Costa Rica cargadas exitosamente');
    }
}
