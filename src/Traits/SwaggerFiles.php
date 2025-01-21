<?php

namespace LaraSwagger\Traits;

use Illuminate\Support\Facades\Artisan;
use Exception;

trait SwaggerFiles
{
  private function saveSwaggerFile($swagger)
  {
    try {
      $apiDocsPath = public_path('api-docs');
      if (!is_dir($apiDocsPath)) {
        if (!mkdir($apiDocsPath, 0755, true)) {
          throw new Exception('Failed to create directory: ' . $apiDocsPath);
        }
      }

      $filePath = $apiDocsPath . '/api-docs.json';
      if (file_put_contents($filePath, json_encode($swagger, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to write Swagger file to: ' . $filePath);
      }
    } catch (Exception $e) {
      $this->error('Error saving Swagger file: ');
    }
  }

  private function copySwaggerFiles()
  {
    try {
      $sourceDir = base_path('vendor/swagger-api/swagger-ui/dist/');
      $destDir = public_path('api-docs/');

      if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
          throw new Exception('Failed to create directory: ' . $destDir);
        }
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

        if (!file_exists($sourceFile)) {
          throw new Exception('Source file does not exist: ' . $sourceFile);
        }

        if (!file_exists($destFile) && !copy($sourceFile, $destFile)) {
          throw new Exception('Failed to copy file from ' . $sourceFile . ' to ' . $destFile);
        }
      }

      $viewSource = base_path('vendor/bamagid/laraswagger/src/views/index.blade.php');
      $viewDest = resource_path('views/api-docs/index.blade.php');

      if (!file_exists($viewSource)) {
        throw new Exception('View source file does not exist: ' . $viewSource);
      }

      if (!file_exists($viewDest)) {
        if (!is_dir(dirname($viewDest))) {
          if (!mkdir(dirname($viewDest), 0755, true)) {
            throw new Exception('Failed to create directory for view: ' . dirname($viewDest));
          }
        }
        if (!copy($viewSource, $viewDest)) {
          throw new Exception('Failed to copy view file to: ' . $viewDest);
        }
      }

      $apiRoutesPath = base_path('routes/api.php');
      $routeToInsert = "Route::get('documentation', function () {\n    return view('api-docs.index');\n});";

      if (file_exists($apiRoutesPath)) {
        $apiRoutesContent = file_get_contents($apiRoutesPath);

        if (strpos($apiRoutesContent, "Route::get('documentation'") === false) {
          if (file_put_contents($apiRoutesPath, $routeToInsert . PHP_EOL, FILE_APPEND) === false) {
            throw new Exception('Failed to update API routes file: ' . $apiRoutesPath);
          }
          Artisan::call('route:clear');
        }
      } else {
        throw new Exception('API routes file does not exist: ' . $apiRoutesPath);
      }
    } catch (Exception $e) {
      $this->error('Error copying Swagger files: ' . $files);
    }
  }
}
