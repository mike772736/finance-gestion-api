<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your application. By default,
    | CORS is disabled for security. Configure allowed origins, methods, etc.
    |
    */


    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

'allowed_origins' => [
    'https://finance-gestion-client.vercel.app',
    'http://localhost:5173', // Pour que ça continue de marcher sur ton PC
],
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];