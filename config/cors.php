<?php

return [


    'paths' => ['api/*', 'register', 'login', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
  'https://symatechfrontend.vercel.app',
        'https://symatech-assesment-frontend.vercel.app',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173'
    ],

    'allowed_origins_patterns' => [
        'https://symatech-assesment-frontend-*-emmas-projects-*.vercel.app',
        'https://symatech-assesment-frontend*.vercel.app',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
