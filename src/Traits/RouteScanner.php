<?php

namespace LaraSwagger\Traits;

use Illuminate\Support\Facades\Route;
use LaraSwagger\Exceptions\SwaggerGenerationException;

trait RouteScanner
{
  private function scanRoutes()
  {
    try {
      $routes = collect(Route::getRoutes())->flatMap(function ($route) {
        $uri = $route->uri;
        $method = $route->methods;
        $action = $route->action['uses'] ?? null;

        if (is_array($action) && isset($action['middleware'])) {
          $subRoutes = collect(Route::getRoutes())->filter(function ($subRoute) use ($action) {
            return in_array($action['middleware'], $subRoute->middleware());
          });

          return $subRoutes->map(function ($subRoute) {
            return [
              'uri' => $subRoute->uri,
              'method' => $subRoute->methods,
              'action' => $subRoute->action['uses'] ?? null,
            ];
          });
        }

        return [
          [
            'uri' => $uri,
            'method' => $method,
            'action' => $action,
          ],
        ];
      });

      return $routes->filter(function ($route) {
        return strpos($route['uri'], 'api/') === 0 && ! in_array($route['uri'], ['api/documentation', 'api/oauth2-callback']);
      })->values();
    } catch (\Throwable $e) {
      $this->issues[] = SwaggerGenerationException::routeIntrospectionFailed(
        '-',
        'Impossible de lister les routes de l\'application: '.$e->getMessage(),
        'Vérifiez que routes/api.php se charge sans erreur (php artisan route:list).',
      );

      return collect();
    }
  }

  private function generateParameters($uri)
  {
    try {
      preg_match_all('/\{(\w+)\}/', $uri, $matches);
      $parameters = [];
      foreach ($matches[1] as $param) {
        $parameters[] = [
          'in' => 'path',
          'name' => $param,
          'required' => true,
          'schema' => [
            'type' => 'string',
          ],
        ];
      }
      $parameters[] = [
        'in' => 'header',
        'name' => 'User-Agent',
        'schema' => [
          'type' => 'string',
        ],
      ];

      return $parameters;
    } catch (\Throwable $e) {
      $this->issues[] = SwaggerGenerationException::routeIntrospectionFailed(
        $uri,
        'Impossible de générer les paramètres de route: '.$e->getMessage(),
        "Vérifiez la déclaration de la route \"{$uri}\".",
      );

      return [];
    }
  }

  private function normalizeMethod($methods)
  {
    if (! is_array($methods) || empty($methods)) {
      $this->issues[] = SwaggerGenerationException::routeIntrospectionFailed(
        is_string($methods) ? $methods : json_encode($methods),
        'Aucune méthode HTTP valide trouvée pour cette route.',
        'Vérifiez la déclaration de la route correspondante dans routes/api.php.',
      );

      return 'UNKNOWN';
    }

    if (in_array('GET', $methods, true)) {
      return 'GET';
    }
    if (in_array('PUT', $methods, true)) {
      return 'PUT';
    }

    return strtoupper($methods[0]);
  }
}
