<?php

namespace LaraSwagger\Traits;

use Illuminate\Support\Facades\Artisan;

trait SwaggerFiles
{


  private function saveSwaggerFile($swagger)
  {
    $apiDocsPath = public_path('api-docs');
    if (!is_dir($apiDocsPath)) {
      mkdir($apiDocsPath, 0755, true);
    }
    file_put_contents($apiDocsPath . '/api-docs.json', json_encode($swagger, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  }

  private function copySwaggerFiles()
  {
    $sourceDir = base_path('vendor/swagger-api/swagger-ui/dist/');
    $destDir = public_path('api-docs/');

    if (!is_dir($destDir)) {
      mkdir($destDir, 0755, true);
    }

    $files = [
      'swagger-ui.css',
      'swagger-ui-bundle.js',
      'swagger-ui-standalone-preset.js',
      'favicon-16x16.png',
      'favicon-32x32.png',
      'index.css',
    ];

    foreach ($files as $file) {
      $sourceFile = $sourceDir . $file;
      $destFile = $destDir . $file;

      if (!file_exists($destFile)) {
        copy($sourceFile, $destFile);
      }
    }
    $viewSource = base_path('packages/laravel-swagger/src/views/index.blade.php');
    $viewDest = resource_path('views/api-docs/index.blade.php');

    if (!file_exists($viewDest)) {
      if (!is_dir(dirname($viewDest))) {
        mkdir(dirname($viewDest), 0755, true);
      }
      copy($viewSource, $viewDest);
    }
    $apiRoutesPath = base_path('routes/api.php');
    $routeToInsert = "Route::get('documentation', function () {
  return view('api-docs.index');
});";

    if (file_exists($apiRoutesPath)) {
      $apiRoutesContent = file_get_contents($apiRoutesPath);
      if (!strpos($apiRoutesContent, "Route::get('documentation'")) {
        file_put_contents($apiRoutesPath, $routeToInsert . PHP_EOL, FILE_APPEND);
        Artisan::call('route:clear');
      }
    }
  }
}
