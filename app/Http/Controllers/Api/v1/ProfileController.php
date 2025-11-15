<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Mostrar el perfil del usuario autenticado.
     * GET /api/v1/profile
     */
    public function show()
    {
        $user = auth()->user();

        // Cargar relaciones necesarias
        $user->load(['addresses' => function ($query) {
            $query->orderBy('is_default', 'desc');
        }]);

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Actualizar el perfil del usuario autenticado.
     * PUT /api/v1/profile
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        // Si se proporciona password, hashearlo
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Si no se proporciona password, no actualizar ese campo
            unset($data['password']);
        }

        // Actualizar usuario
        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->fresh()
        ]);
    }
}
