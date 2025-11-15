<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StoreAddressRequest;
use App\Http\Requests\v1\UpdateAddressRequest;
use App\Models\Address;

class AddressController extends Controller
{
    /**
     * Listar todas las direcciones del usuario autenticado.
     * GET /api/v1/addresses
     */
    public function index()
    {
        $addresses = Address::forUser(auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'addresses' => $addresses
        ]);
    }

    /**
     * Crear una nueva dirección.
     * POST /api/v1/addresses
     */
    public function store(StoreAddressRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // Si es la primera dirección del usuario, marcarla como predeterminada automáticamente
        $existingAddressesCount = Address::forUser(auth()->id())->count();

        if ($existingAddressesCount === 0) {
            $data['is_default'] = true;
        }

        // Si se marca como predeterminada, desmarcar las demás
        if (!empty($data['is_default']) && $data['is_default']) {
            Address::forUser(auth()->id())->update(['is_default' => false]);
        }

        $address = Address::create($data);

        return response()->json([
            'message' => 'Dirección creada exitosamente',
            'address' => $address
        ], 201);
    }

    /**
     * Mostrar una dirección específica.
     * GET /api/v1/addresses/{id}
     */
    public function show($id)
    {
        $address = Address::forUser(auth()->id())->findOrFail($id);

        return response()->json([
            'address' => $address
        ]);
    }

    /**
     * Actualizar una dirección existente.
     * PUT /api/v1/addresses/{id}
     */
    public function update(UpdateAddressRequest $request, $id)
    {
        $address = Address::forUser(auth()->id())->findOrFail($id);

        $data = $request->validated();

        // Si se marca como predeterminada, desmarcar las demás
        if (!empty($data['is_default']) && $data['is_default']) {
            Address::forUser(auth()->id())
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'message' => 'Dirección actualizada exitosamente',
            'address' => $address->fresh()
        ]);
    }

    /**
     * Eliminar una dirección.
     * DELETE /api/v1/addresses/{id}
     */
    public function destroy($id)
    {
        $address = Address::forUser(auth()->id())->findOrFail($id);

        $wasDefault = $address->is_default;
        $address->delete();

        // Si era la dirección predeterminada, asignar la primera disponible como nueva predeterminada
        if ($wasDefault) {
            $newDefault = Address::forUser(auth()->id())->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json([
            'message' => 'Dirección eliminada exitosamente'
        ]);
    }

    /**
     * Marcar dirección como predeterminada.
     * POST /api/v1/addresses/{id}/set-default
     */
    public function setDefault($id)
    {
        $address = Address::forUser(auth()->id())->findOrFail($id);

        // Desmarcar todas las direcciones del usuario
        Address::forUser(auth()->id())->update(['is_default' => false]);

        // Marcar esta dirección como predeterminada
        $address->update(['is_default' => true]);

        return response()->json([
            'message' => 'Dirección marcada como predeterminada',
            'address' => $address->fresh()
        ]);
    }
}
