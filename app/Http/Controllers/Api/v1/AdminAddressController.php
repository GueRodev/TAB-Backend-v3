<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAddressController extends Controller
{
    /**
     * Listar todas las direcciones (con paginación y filtros).
     * GET /api/v1/admin/addresses
     */
    public function index(Request $request)
    {
        $query = Address::with('user:id,name,email');

        // Filtro por usuario
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por provincia
        if ($request->has('province')) {
            $query->where('province', $request->province);
        }

        // Filtro por cantón
        if ($request->has('canton')) {
            $query->where('canton', $request->canton);
        }

        // Filtro por distrito
        if ($request->has('district')) {
            $query->where('district', $request->district);
        }

        // Ordenar por defecto y fecha de creación
        $query->orderBy('is_default', 'desc')
              ->orderBy('created_at', 'desc');

        // Paginación (15 por página por defecto)
        $perPage = $request->input('per_page', 15);
        $addresses = $query->paginate($perPage);

        return response()->json($addresses);
    }

    /**
     * Mostrar una dirección específica.
     * GET /api/v1/admin/addresses/{id}
     */
    public function show($id)
    {
        $address = Address::with('user:id,name,email,phone')->findOrFail($id);

        return response()->json([
            'address' => $address
        ]);
    }

    /**
     * Obtener todas las direcciones de un usuario específico.
     * GET /api/v1/admin/users/{userId}/addresses
     */
    public function byUser($userId)
    {
        // Verificar que el usuario existe
        $user = User::findOrFail($userId);

        $addresses = Address::forUser($userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'addresses' => $addresses,
            'total' => $addresses->count()
        ]);
    }
}
