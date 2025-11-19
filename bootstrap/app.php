<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ========================================
        // DESHABILITADO: statefulApi() requiere CSRF tokens
        // ========================================
        // Comentado porque estamos usando Bearer Token Authentication (Sanctum)
        // Si en el futuro usas SPA mode con cookies, descomenta la siguiente línea:
        // $middleware->statefulApi();

        // ========================================
        // REGISTRAR MIDDLEWARE DE SPATIE
        // ========================================
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        // ========================================
        // FORZAR RESPUESTAS JSON PARA RUTAS API
        // ========================================
        // Todas las rutas que empiecen con /api/* siempre retornarán JSON
        // incluso si no incluyen el header Accept: application/json
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            return $request->is('api/*');
        });
    })->create();