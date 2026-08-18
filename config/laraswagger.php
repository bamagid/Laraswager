<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API title & description
    |--------------------------------------------------------------------------
    |
    | Displayed at the top of the generated Swagger/OpenAPI documentation.
    | Falls back to your application name when not explicitly configured.
    |
    */

    'title' => env('APP_NAME', config('app.name', 'Laravel')),

    'description' => env('APP_DESCRIPTION', ''),

    /*
    |--------------------------------------------------------------------------
    | Auto generation
    |--------------------------------------------------------------------------
    |
    | When enabled, the documentation is regenerated automatically whenever
    | an artisan command runs and while `php artisan serve` is active.
    | Set to false to only regenerate manually via `php artisan swagger:generate`.
    |
    */

    'auto_generate' => (bool) env('AUTO_GENERATE_DOCS', true),

    /*
    |--------------------------------------------------------------------------
    | Documentation route
    |--------------------------------------------------------------------------
    |
    | The URI (relative to your app URL) where the Swagger UI is served.
    |
    */

    'route' => env('LARASWAGGER_ROUTE', 'api/documentation'),

];
