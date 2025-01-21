<?php

namespace LaraSwagger\Traits;

use Illuminate\Support\Facades\Route;
use Exception;

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
        return strpos($route['uri'], 'api/') === 0 && !in_array($route['uri'], ['api/documentation', 'api/oauth2-callback']);
      })->values();
    } catch (Exception $e) {
      $this->error("Error scanning routes: " . $e->getMessage());
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
    } catch (Exception $e) {
      $this->error("Error generating parameters for URI '{$uri}' ");
      return [];
    }
  }

  private function normalizeMethod($methods)
  {
    try {
      if (is_array($methods)) {
        if (in_array('GET', $methods)) {
          return 'GET';
        }
        if (in_array('PUT', $methods)) {
          return 'PUT';
        }
      }
      return strtoupper($methods[0]);
    } catch (Exception $e) {
      $this->error("Error normalizing methods: " . $methods[0]);
      return 'UNKNOWN';
    }
  }
}
