<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrLocation extends Model
{
    protected $fillable = [
        'province_id',
        'canton_id',
        'province_name',
        'canton_name',
        'district_name',
        'type',
    ];

    /**
     * Obtener estructura jerárquica completa
     * Retorna array con provincias > cantones > distritos
     *
     * IMPORTANTE: Los IDs retornados son los 'id' de los registros (autoincremental),
     * NO los province_id o canton_id del JSON original.
     * Estos IDs son los que se deben usar en el frontend para shipping_address.
     */
    public static function getHierarchy(): array
    {
        $locations = self::all();

        $provinces = [];

        // Iterar sobre las provincias
        foreach ($locations->where('type', 'province') as $province) {
            $provinceData = [
                'id' => $province->id,  // ID del registro (para usar en el frontend)
                'nombre' => $province->province_name,
                'cantones' => []
            ];

            // Obtener cantones de esta provincia
            $cantones = $locations->where('type', 'canton')
                ->where('province_id', $province->province_id);

            foreach ($cantones as $canton) {
                $cantonData = [
                    'id' => $canton->id,  // ID del registro (para usar en el frontend)
                    'nombre' => $canton->canton_name,
                    'distritos' => []
                ];

                // Obtener distritos de este cantón
                // CAMBIO: Ahora retorna objetos con id y nombre en lugar de solo strings
                $distritos = $locations->where('type', 'district')
                    ->where('province_id', $province->province_id)
                    ->where('canton_id', $canton->canton_id)
                    ->map(function ($distrito) {
                        return [
                            'id' => $distrito->id,  // ID del registro (para usar en el frontend)
                            'nombre' => $distrito->district_name
                        ];
                    })
                    ->values()  // Reindexa el array
                    ->toArray();

                $cantonData['distritos'] = $distritos;
                $provinceData['cantones'][] = $cantonData;
            }

            $provinces[] = $provinceData;
        }

        return $provinces;
    }

    /**
     * Validar que existe una ubicación específica
     *
     * @param string $province Nombre de la provincia
     * @param string $canton Nombre del cantón
     * @param string $district Nombre del distrito
     * @return bool
     */
    public static function locationExists(string $province, string $canton, string $district): bool
    {
        return self::where('type', 'district')
            ->where('province_name', $province)
            ->where('canton_name', $canton)
            ->where('district_name', $district)
            ->exists();
    }
}
