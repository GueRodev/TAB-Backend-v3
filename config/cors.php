<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],   // Permite las rutas de la API (CSRF no necesario en API Token mode)

    'allowed_methods' => ['*'],     // Permite todos los metodos HTTP (GET, POST, PUT, DELETE, etc.)

    'allowed_origins' => [
    'http://localhost:8081',        // Tu frontend en desarrollo
    'http://localhost:3000',        // Puerto por defecto de React
    'http://localhost:5173',        // Puerto por defecto de Vite
    'http://192.168.100.69:8081',   // Tu red local
                                    // Dominio Produccion Futuro                        
    ],

    'allowed_origins_patterns' => [],   // Permite patrones de origen para más flexibilidad

    'allowed_headers' => ['*'],   // Permite todos los headers (Content-Type, Authorization, etc.)

    'exposed_headers' => [],   // Permite headers que se pueden exponer al cliente

    'max_age' => 0,   // Permite el uso de credenciales en las solicitudes

    'supports_credentials' => false,   // false para API Token mode (Bearer tokens), true solo para SPA mode con cookies

];
