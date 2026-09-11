<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use LaraSwagger\Support\SwaggerDocsPath;

Route::get(config('laraswagger.route', 'api/documentation'), function () {
    return view('laraswagger::index');
});

Route::get('api-docs/api-docs.json', function () {
    $path = SwaggerDocsPath::specFile();

    if (! file_exists($path)) {
        Artisan::call('swagger:generate');
    }

    if (! file_exists($path)) {
        abort(404, 'La documentation n\'a pas encore pu être générée.');
    }

    return response()->file($path, ['Content-Type' => 'application/json']);
});

Route::get('api-docs/{file}', function (string $file) {
    $asset = SwaggerDocsPath::assetFile($file);

    abort_if($asset === null, 404);

    return response()->file($asset['path'], ['Content-Type' => $asset['mime']]);
})->where('file', '.*');
