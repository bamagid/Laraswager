<?php

namespace LaraSwagger\Traits;

use LaraSwagger\Exceptions\SwaggerGenerationException;

trait SwaggerFiles
{
  private function saveSwaggerFile($swagger)
  {
    try {
      $apiDocsPath = public_path('api-docs');
      if (! is_dir($apiDocsPath)) {
        if (! mkdir($apiDocsPath, 0755, true)) {
          throw new \RuntimeException('Impossible de créer le dossier: '.$apiDocsPath);
        }
      }

      $filePath = $apiDocsPath.'/api-docs.json';
      if (file_put_contents($filePath, json_encode($swagger, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) === false) {
        throw new \RuntimeException('Impossible d\'écrire le fichier: '.$filePath);
      }
    } catch (\Throwable $e) {
      $this->issues[] = SwaggerGenerationException::routeIntrospectionFailed(
        '-',
        "Échec de l'écriture du fichier Swagger: ".$e->getMessage(),
        "Vérifiez les droits d'écriture sur le dossier public/api-docs.",
      );
    }
  }

  private function copySwaggerFiles()
  {
    try {
      $sourceDir = base_path('vendor/swagger-api/swagger-ui/dist/');
      $destDir = public_path('api-docs/');

      if (! is_dir($destDir)) {
        if (! mkdir($destDir, 0755, true)) {
          throw new \RuntimeException('Failed to create directory: '.$destDir);
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
        $sourceFile = $sourceDir.$file;
        $destFile = $destDir.$file;

        if (! file_exists($sourceFile)) {
          throw new \RuntimeException('Source file does not exist: '.$sourceFile);
        }

        if (! file_exists($destFile) && ! copy($sourceFile, $destFile)) {
          throw new \RuntimeException('Failed to copy file from '.$sourceFile.' to '.$destFile);
        }
      }
    } catch (\Throwable $e) {
      $this->issues[] = SwaggerGenerationException::routeIntrospectionFailed(
        '-',
        'Échec de la copie des fichiers statiques Swagger UI: '.$e->getMessage(),
        "Vérifiez l'installation de swagger-api/swagger-ui.",
      );
    }
  }
}
