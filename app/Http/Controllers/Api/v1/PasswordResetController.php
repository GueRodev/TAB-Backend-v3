<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Enviar enlace de restablecimiento de contraseña
     *
     * POST /api/v1/auth/password/forgot
     */
    public function sendResetLink(Request $request)
    {
        // Validar email
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico debe ser válido',
        ]);

        // Verificar que el usuario existe
        $user = User::where('email', $request->email)->first();

        // Por seguridad, siempre retornamos el mismo mensaje
        // (no revelamos si el email existe o no)
        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'Si el correo existe en nuestro sistema, recibirás un enlace de recuperación.',
            ], 200);
        }

        // Enviar el enlace de restablecimiento
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Verificar el estado del envío
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Te hemos enviado un enlace de recuperación a tu correo electrónico.',
            ], 200);
        }

        // Si hubo un error al enviar (ej: throttling)
        if ($status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages([
                'email' => ['Por favor espera antes de volver a intentarlo.'],
            ]);
        }

        // Error genérico
        return response()->json([
            'success' => false,
            'message' => 'No se pudo enviar el enlace de recuperación. Inténtalo más tarde.',
        ], 500);
    }

    /**
     * Restablecer la contraseña
     *
     * POST /api/v1/auth/password/reset
     */
    public function reset(Request $request)
    {
        // Validar datos
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'token.required' => 'El token es requerido',
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico debe ser válido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        // Intentar restablecer la contraseña
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // Actualizar la contraseña del usuario
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                // Enviar email de confirmación
                Mail::to($user->email)->send(new PasswordChangedMail($user));

                // Opcional: Revocar todos los tokens existentes para mayor seguridad
                // $user->tokens()->delete();
            }
        );

        // Verificar el estado
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión.',
            ], 200);
        }

        // Manejar diferentes errores
        if ($status === Password::INVALID_TOKEN) {
            throw ValidationException::withMessages([
                'token' => ['El enlace de recuperación es inválido o ha expirado.'],
            ]);
        }

        if ($status === Password::INVALID_USER) {
            throw ValidationException::withMessages([
                'email' => ['No pudimos encontrar un usuario con ese correo electrónico.'],
            ]);
        }

        // Error genérico
        return response()->json([
            'success' => false,
            'message' => 'No se pudo restablecer la contraseña. Por favor, solicita un nuevo enlace.',
        ], 500);
    }
}
