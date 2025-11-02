<?php

namespace App\Http\Controllers;

use App\Mail\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Exception;

/**
 * Controlador para probar configuración de emails
 * 
 * @package App\Http\Controllers
 */
class TestMailController extends Controller
{
    /**
     * Enviar email de prueba
     * 
     * POST /api/test-email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTestEmail(Request $request)
    {
        // Validar que se proporcione un email destino
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Enviar email de prueba
            Mail::to($validated['email'])->send(new TestEmail());

            return response()->json([
                'success' => true,
                'message' => '✅ Email de prueba enviado correctamente',
                'data' => [
                    'recipient' => $validated['email'],
                    'from' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'mailer' => config('mail.default'),
                    'sent_at' => now()->toIso8601String(),
                ],
                'timestamp' => now()->toIso8601String(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Error al enviar el email',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}