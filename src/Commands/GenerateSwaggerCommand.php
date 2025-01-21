<?php

namespace LaraSwagger\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use LaraSwagger\Traits\RouteScanner;
use LaraSwagger\Traits\SwaggerFiles;
use LaraSwagger\Traits\SwaggerPropertiesTrait;
use LaraSwagger\Traits\TypeMapping;

class GenerateSwaggerCommand extends Command
{
  use SwaggerPropertiesTrait, SwaggerFiles, RouteScanner, TypeMapping;
  protected $signature = 'swagger:generate';
  protected $description = 'Generate Swagger documentation based on migrations and/or validations';
  public function handle()
  {
    $this->copySwaggerFiles();
    $routes = $this->scanRoutes();

    $swagger = [
      'openapi' => '3.0.0',
      'info' => [
        'title' => env("APP_NAME"),
        'description' => env('APP_DESCRIPTION'),
        'version' => '1.0.0'
      ],
      'security' => [['BearerAuth' => []]],
      'components' => ['securitySchemes' => ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT']]],
      'consumes' => ["multipart/form-data"],
      'paths' => [],
    ];

    foreach ($routes as $route) {
      if (is_string($route['action'])) {
        $controllerAction = explode('@', $route['action']);
        $controller = $controllerAction[0];
        $method = $controllerAction[1];
        $reflection = new ReflectionClass($controller);
        $methodReflection = $reflection->getMethod($method);
        $docComment = $methodReflection->getDocComment();
        $tableName = $this->getTableNameFromController($controller);
        $columns = Schema::getColumnListing($tableName);
        $summary = '';
        if ($docComment && preg_match('/summary\s=\s*(.*?)(\s*\*|\s*$)/', $docComment, $matches)) {
          $summary = trim($matches[1]);
        }
        $validations = $this->getValidationsFromMethod($controller, $method);
        if (!empty($validations)) {
          [$properties, $requiredFields] = $this->generatePropertiesFromValidations($validations);
        } else {
          $properties = $this->generateProperties($tableName, $columns);
          $requiredFields = [];
        }
        $httpMethod = $this->normalizeMethod($route['method']);
        $path = '/' . $route['uri'];
        $swagger['paths'][$path][strtolower($httpMethod)] = [
          'summary' => $summary,
          'tags' => [ucwords(str_replace('_', ' ', $tableName))],
          'responses' => $this->generateResponses(),
        ];

        if ($httpMethod == 'POST' || $httpMethod == 'PUT') {
          $schema = [
            'type' => 'object',
            'properties' => $properties,
          ];

          if (!empty($requiredFields)) {
            $schema['required'] = $requiredFields;
          }

          $swagger['paths'][$path][strtolower($httpMethod)]['requestBody'] = [
            'content' => [
              'multipart/form-data' => [
                'schema' => $schema,
                'example' => $this->generateExample($columns),
              ],
            ],
          ];
        }

        $parameters = $this->generateParameters($route['uri']);
        if (!empty($parameters)) {
          $swagger['paths'][$path][strtolower($httpMethod)]['parameters'] = $parameters;
        }
      } else {
        $httpMethod = $this->normalizeMethod($route['method']);
        $path = '/' . $route['uri'];
        if ($path == '/api/documentation') {
          return;
        }
        $parameters = $this->generateParameters($route['uri']);
        $swagger['paths'][$path][strtolower($httpMethod)] = [
          'summary' => '',
          'tags' => ['Autres'],
          'responses' => $this->generateResponses(),
        ];
        if (!empty($parameters)) {
          $swagger['paths'][$path][strtolower($httpMethod)]['parameters'] = $parameters;
        }
      }
    }
    $this->saveSwaggerFile($swagger);
    $this->info('Swagger documentation generated successfully!');
  }

  private function generateExample($columns)
  {
    $example = [];
    foreach ($columns as $column) {
      if ($column == 'image') {
        $example[$column] = 'binary data';
      } else {
        $example[$column] = 'example ' . $column;
      }
    }
    return $example;
  }
  private function generateResponses()
  {
    return [
      '200' => [
        'description' => 'OK',
        'content' => [
          'application/json' => [
            'schema' => [],
            'example' => '',
          ],
        ],
      ],
      '404' => [
        'description' => 'Not Found',
        'content' => [
          'application/json' => [
            'schema' => [],
            'example' => '',
          ],
        ],
      ],
      '500' => [
        'description' => 'Internal Server Error',
        'content' => [
          'application/json' => [
            'schema' => [],
            'example' => '',
          ],
        ],
      ],
    ];
  }
  private function getTableNameFromController($controller)
  {
    $baseName = str_replace('Controller', '', class_basename($controller));
    return strtolower($baseName) . 's';
  }
  private function getValidationsFromMethod($controller, $method)
  {
    $reflection = new ReflectionClass($controller);
    $methodReflection = $reflection->getMethod($method);
    $params = $methodReflection->getParameters();

    foreach ($params as $param) {
      $paramClass = $param->getClass();

      if ($methodReflection->isUserDefined()) {
        $methodCode = file($reflection->getFileName());
        $methodStartLine = $methodReflection->getStartLine() - 1;
        $methodEndLine = $methodReflection->getEndLine();
        $methodBody = array_slice($methodCode, $methodStartLine, $methodEndLine - $methodStartLine);
        $methodBodyString = implode("", $methodBody);
        $regexArray = [
          '/\$request->validate\((\[\s*\S.*?\s*\])\)/s',
          '/\$(\w+)\s*=\s*Validator::make\(([^;]*)\);/s',
          '/\$rules\s*=\s* (\[\s*\S.*?\s*\]);/s'
        ];

        foreach ($regexArray as $regex) {

          if (preg_match($regex, $methodBodyString, $matches)) {
            if (isset($matches[2])) {
              $validationArrayString = preg_replace('/\$\w+(\->\w+(\(\))?)*,/', '', $matches[2]);
            } else {
              $validationArrayString = $matches[1];
            }
            $validationArrayString = preg_replace('/\.\s*\$\w+(->\w+)*/', '', $validationArrayString);
            return eval('return ' . $validationArrayString . ';');
          }
        }
      }
      if ($paramClass && is_subclass_of($paramClass->name, 'Illuminate\Foundation\Http\FormRequest')) {
        $formRequest = new $paramClass->name();

        $validationArray = $formRequest->rules();
        return $validationArray;
      }
    }

    return [];
  }
}
