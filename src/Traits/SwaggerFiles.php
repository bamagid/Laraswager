<?php

namespace LaraSwagger\Traits;

use LaraSwagger\Exceptions\SwaggerGenerationException;
use LaraSwagger\Support\SwaggerDocsPath;

trait SwaggerFiles
{
  private function saveSwaggerFile($swagger)
  {
    try {
      $filePath = SwaggerDocsPath::specFile();
      $dir = dirname($filePath);
      if (! is_dir($dir)) {
        if (! mkdir($dir, 0755, true)) {
          throw new \RuntimeException('Impossible de créer le dossier: '.$dir);
        }
      }

      if (file_put_contents($filePath, json_encode($swagger, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) === false) {
        throw new \RuntimeException('Impossible d\'écrire le fichier: '.$filePath);
      }
    } catch (\Throwable $e) {
      $this->issues[] = SwaggerGenerationException::routeIntrospectionFailed(
        '-',
        "Échec de l'écriture du fichier Swagger: ".$e->getMessage(),
        "Vérifiez les droits d'écriture sur le dossier storage/app.",
      );
    }
  }
}
