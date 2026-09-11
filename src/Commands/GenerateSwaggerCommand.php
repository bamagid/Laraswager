<?php

namespace LaraSwagger\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LaraSwagger\Exceptions\SwaggerGenerationException;
use LaraSwagger\Support\ValidationRuleExtractor;
use LaraSwagger\Traits\RouteScanner;
use LaraSwagger\Traits\SwaggerFiles;
use LaraSwagger\Traits\SwaggerPropertiesTrait;
use LaraSwagger\Traits\TypeMapping;
use ReflectionClass;

class GenerateSwaggerCommand extends Command
{
  use RouteScanner, SwaggerFiles, SwaggerPropertiesTrait, TypeMapping;

  protected $signature = 'swagger:generate';

  protected $description = 'Generate Swagger documentation based on migrations and/or validations';

  /**
   * Issues collected while generating the documentation. Populated by the
   * traits used above; printed as a summary table at the end of handle().
   *
   * @var SwaggerGenerationException[]
   */
  private $issues = [];

  public function handle()
  {
    $this->issues = [];

    try {
      $this->generate();
    } catch (\Throwable $e) {
      $this->reportIssues();
      $this->renderFatalError($e);

      return 1;
    }

    return 0;
  }

  private function generate()
  {
    $routes = $this->scanRoutes();

    if ($routes->isEmpty() && ! file_exists(base_path('routes/api.php'))) {
      $this->warn('Aucune route API trouvée et aucun fichier routes/api.php détecté : ce package ne documente que les routes de l\'API.');
      $this->warn('Exécutez `php artisan install:api` (ou créez routes/api.php vous-même), puis relancez `php artisan swagger:generate`.');

      return;
    }

    $swagger = [
      'openapi' => '3.0.0',
      'info' => [
        'title' => config('laraswagger.title'),
        'description' => config('laraswagger.description'),
        'version' => '1.0.0',
      ],
      'security' => [['BearerAuth' => []]],
      'components' => ['securitySchemes' => ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT']]],
      'consumes' => ['multipart/form-data'],
      'paths' => [],
    ];

    foreach ($routes as $route) {
      if (is_string($route['action'])) {
        $controllerAction = explode('@', $route['action']);
        $controller = $controllerAction[0];
        $method = $controllerAction[1];

        try {
          $reflection = new ReflectionClass($controller);
          $methodReflection = $reflection->getMethod($method);
        } catch (\ReflectionException $e) {
          $this->issues[] = SwaggerGenerationException::routeIntrospectionFailed(
            '/'.$route['uri'],
            "Le contrôleur ou la méthode \"{$route['action']}\" est introuvable: ".$e->getMessage(),
            'Vérifiez le contrôleur et la méthode de cette route.',
          );

          continue;
        }

        $docComment = $methodReflection->getDocComment();
        if ($docComment && preg_match('/@swagger-ignore\b/', $docComment)) {
          continue;
        }

        $tableName = $this->getTableNameFromController($controller);
        $summary = '';
        if ($docComment && preg_match('/@summary\s+(.*)/', $docComment, $matches)) {
          $summary = trim($matches[1]);
        }

        $validations = $this->getValidationsFromMethod($controller, $method);
        if (! empty($validations)) {
          [$properties, $requiredFields] = $this->generatePropertiesFromValidations($validations, $controller, $method);
        } else {
          // Only touch the database when there is no validation to read properties from,
          // and never let a DB/connection failure abort the whole command.
          try {
            $columns = Schema::hasTable($tableName) ? Schema::getColumnListing($tableName) : [];
          } catch (\Throwable $e) {
            $this->issues[] = SwaggerGenerationException::columnIntrospectionFailed(
              $tableName,
              'Impossible de lire les colonnes depuis la base de données: '.$e->getMessage(),
              'Vérifiez votre configuration de base de données.',
            );
            $columns = [];
          }
          $properties = $this->generateProperties($tableName, $columns, $controller, $method);
          $requiredFields = [];
        }

        $httpMethod = $this->normalizeMethod($route['method']);
        $path = '/'.$route['uri'];
        $swagger['paths'][$path][strtolower($httpMethod)] = [
          'summary' => $summary,
          'tags' => [ucwords(str_replace('_', ' ', $tableName))],
          'responses' => $this->generateResponses(),
        ];

        if ($httpMethod == 'POST') {
          $schema = [
            'type' => 'object',
            'properties' => $properties,
          ];

          if (! empty($requiredFields)) {
            $schema['required'] = $requiredFields;
          }

          $swagger['paths'][$path][strtolower($httpMethod)]['requestBody'] = [
            'content' => [
              'multipart/form-data' => [
                'schema' => $schema,
              ],
            ],
          ];
        }
        if ($httpMethod == 'PUT' || $httpMethod == 'PATCH') {
          $schema = [
            'type' => 'object',
            'properties' => $properties,
          ];

          if (! empty($requiredFields)) {
            $schema['required'] = $requiredFields;
          }

          $swagger['paths'][$path][strtolower($httpMethod)]['requestBody'] = [
            'content' => [
              'application/x-www-form-urlencoded' => [
                'schema' => $schema,
              ],
            ],
          ];
        }

        $parameters = $this->generateParameters($route['uri']);
        if (! empty($parameters)) {
          $swagger['paths'][$path][strtolower($httpMethod)]['parameters'] = $parameters;
        }
      } else {
        $httpMethod = $this->normalizeMethod($route['method']);
        $path = '/'.$route['uri'];
        if ($path == '/api/documentation') {
          continue;
        }
        $parameters = $this->generateParameters($route['uri']);
        $swagger['paths'][$path][strtolower($httpMethod)] = [
          'summary' => '',
          'tags' => ['Autres'],
          'responses' => $this->generateResponses(),
        ];
        if (! empty($parameters)) {
          $swagger['paths'][$path][strtolower($httpMethod)]['parameters'] = $parameters;
        }
      }
    }
    $this->saveSwaggerFile($swagger);
    $this->reportIssues();
    $this->info('Swagger documentation generated successfully!');
  }

  private function reportIssues()
  {
    // Cosmetic fallbacks (e.g. an unrecognized rule falling back to "type":
    // "string") still produce complete documentation and aren't worth
    // surfacing. Only issues that leave something genuinely undocumented
    // are shown.
    $blockingIssues = array_values(array_filter($this->issues, function (SwaggerGenerationException $issue) {
      return $issue->blocking();
    }));

    $this->issues = [];

    if (empty($blockingIssues)) {
      return;
    }

    if ($this->promptsAvailable()) {
      $this->reportIssuesWithPrompts($blockingIssues);
    } else {
      $this->reportIssuesPlain($blockingIssues);
    }
  }

  /**
   * laravel/prompts ships a terminal-width-aware table (unlike Symfony's
   * Table helper, which doesn't wrap long cell content), so we prefer it
   * when it's installed. It only supports Laravel 10+, so anything older
   * falls back to reportIssuesPlain().
   */
  private function promptsAvailable(): bool
  {
    return function_exists('Laravel\Prompts\warning') && function_exists('Laravel\Prompts\table');
  }

  /**
   * @param  SwaggerGenerationException[]  $issues
   */
  private function reportIssuesWithPrompts(array $issues): void
  {
    \Laravel\Prompts\warning(sprintf('%d avertissement(s) bloquant(s) rencontré(s) pendant la génération de la documentation.', count($issues)));

    $rows = [];
    foreach ($issues as $issue) {
      $rows[] = [
        $issue->actionLabel(),
        $issue->field() ?? '-',
        $issue->reason(),
        $issue->suggestion(),
      ];
    }

    \Laravel\Prompts\table(['Contrôleur::méthode / Route', 'Champ', 'Problème', 'Recommandation'], $rows);
  }

  /**
   * @param  SwaggerGenerationException[]  $issues
   */
  private function reportIssuesPlain(array $issues): void
  {
    $this->newLine();
    $this->warn(sprintf('%d avertissement(s) bloquant(s) rencontré(s) pendant la génération de la documentation :', count($issues)));

    foreach ($issues as $issue) {
      $location = $issue->field()
        ? $issue->actionLabel().' — champ "'.$issue->field().'"'
        : $issue->actionLabel();

      $this->newLine();
      $this->line('  <fg=yellow;options=bold>➜</> <options=bold>'.$location.'</>');
      $this->line('    '.$issue->reason());
      $this->line('    <fg=gray>→ '.$issue->suggestion().'</>');
    }
  }

  /**
   * Renders an unexpected (non-recoverable) exception as a clear, bounded
   * error block instead of letting Artisan's default handler dump a raw
   * stack trace to the console.
   */
  private function renderFatalError(\Throwable $e): void
  {
    $details = 'Type : '.get_class($e)."\n"
      .'Message : '.$e->getMessage()."\n"
      .'Origine : '.$e->getFile().':'.$e->getLine();

    if ($this->promptsAvailable() && function_exists('Laravel\Prompts\error') && function_exists('Laravel\Prompts\note')) {
      \Laravel\Prompts\error('La génération de la documentation a été interrompue par une erreur inattendue.');
      \Laravel\Prompts\note($details);
    } else {
      $this->newLine();
      $this->error('La génération de la documentation a été interrompue par une erreur inattendue.');
      $this->line('  <fg=red;options=bold>Type</> : '.get_class($e));
      $this->line('  <fg=red;options=bold>Message</> : '.$e->getMessage());
      $this->line('  <fg=red;options=bold>Origine</> : '.$e->getFile().':'.$e->getLine());
    }

    if ($this->output->isVerbose()) {
      $this->newLine();
      $this->line('<fg=gray>'.$e->getTraceAsString().'</>');
    } else {
      $this->newLine();
      $this->comment('Relancez la commande avec -v pour afficher la pile d\'appels complète.');
    }
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

    return Str::snake(Str::plural($baseName));
  }

  /**
   * @return array<string, mixed>
   */
  private function getValidationsFromMethod($controller, $method)
  {
    $reflection = new ReflectionClass($controller);
    $methodReflection = $reflection->getMethod($method);

    foreach ($methodReflection->getParameters() as $param) {
      $type = $param->getType();
      if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
        continue;
      }

      $paramClass = $type->getName();
      if (is_subclass_of($paramClass, FormRequest::class)) {
        try {
          // is_subclass_of() above guarantees a rules() method at runtime; the dynamic
          // class name just makes it invisible to static analysis.
          // @phpstan-ignore-next-line
          return (new $paramClass)->rules();
        } catch (\Throwable $e) {
          $this->issues[] = SwaggerGenerationException::formRequestResolutionFailed(
            $paramClass,
            $controller,
            $method,
            $e,
            'Vérifiez la méthode rules() de ce FormRequest.',
          );

          return [];
        }
      }
    }

    if (! $methodReflection->isUserDefined()) {
      return [];
    }

    $extractor = new ValidationRuleExtractor;
    $rules = $extractor->extractFromMethod($methodReflection, $controller, $method);
    $this->issues = array_merge($this->issues, $extractor->issues());

    return $rules;
  }
}
