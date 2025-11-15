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
     */
    public static function getHierarchy(): array
    {
        $locations = self::all();

        $provinces = [];

        // Iterar sobre las provincias
        foreach ($locations->where('type', 'province') as $province) {
            $provinceData = [
                'id' => $province->province_id,
                'nombre' => $province->province_name,
                'cantones' => []
            ];

            // Obtener cantones de esta provincia
            $cantones = $locations->where('type', 'canton')
                ->where('province_id', $province->province_id);

            foreach ($cantones as $canton) {
                $cantonData = [
                    'id' => $canton->canton_id,
                    'nombre' => $canton->canton_name,
                    'distritos' => []
                ];

                // Obtener distritos de este cantón
                $distritos = $locations->where('type', 'district')
                    ->where('province_id', $province->province_id)
                    ->where('canton_id', $canton->canton_id)
                    ->pluck('district_name')
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
