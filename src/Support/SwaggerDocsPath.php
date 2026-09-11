<?php

namespace LaraSwagger\Support;

/**
 * Central source of truth for where the generated spec lives and which
 * static Swagger UI assets are servable, so the generator (writing) and
 * the routes (serving) never drift apart.
 */
class SwaggerDocsPath
{
  /**
   * Filenames swagger-api/swagger-ui ships in its dist/ folder that the
   * documentation page needs, mapped to their MIME type. Served directly
   * from vendor/ — never copied into the consuming app's public/
   * directory. The MIME type is set explicitly rather than left to
   * response()->file()'s auto-detection, which often misidentifies .css
   * as text/plain and gets the stylesheet rejected by the browser.
   */
  public const ASSETS = [
    'swagger-ui.css' => 'text/css',
    'swagger-ui-bundle.js' => 'application/javascript',
    'swagger-ui-standalone-preset.js' => 'application/javascript',
    'favicon-16x16.png' => 'image/png',
    'favicon-32x32.png' => 'image/png',
    'index.css' => 'text/css',
  ];

  public static function specFile(): string
  {
    return storage_path('app/laraswagger/api-docs.json');
  }

  /**
   * @return array{path: string, mime: string}|null
   */
  public static function assetFile(string $file): ?array
  {
    if (! isset(self::ASSETS[$file])) {
      return null;
    }

    $path = base_path('vendor/swagger-api/swagger-ui/dist/'.$file);

    return file_exists($path) ? ['path' => $path, 'mime' => self::ASSETS[$file]] : null;
  }
}
