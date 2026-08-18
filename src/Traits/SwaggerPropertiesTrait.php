<?php

namespace LaraSwagger\Traits;

use Illuminate\Support\Facades\Schema;
use LaraSwagger\Exceptions\SwaggerGenerationException;
use LaraSwagger\Support\LiteralPhpEvaluator;

trait SwaggerPropertiesTrait
{
  private function generatePropertiesFromValidations($validations, $controller = null, $method = null)
  {
    $properties = [];
    $requiredFields = [];

    foreach ($validations as $field => $rules) {
      try {
        if (strpos($field, '.*') !== false) {
          $baseField = explode('.*', $field)[0];

          if (! isset($properties[$baseField])) {
            $properties[$baseField] = [
              'type' => 'array',
              'items' => [
                'type' => 'object',
                'properties' => [],
              ],
            ];
          }

          $subField = explode('.*.', $field)[1] ?? null;
          if ($subField) {
            $properties[$baseField]['items']['type'] = 'object';
            $properties[$baseField]['items']['properties'][$subField] = $this->mapValidationTypeToSwaggerType($rules, $controller, $method, $field);
          } else {
            $properties[$baseField]['items'] = $this->mapValidationTypeToSwaggerType($rules, $controller, $method, $field);
          }
        } else {
          $properties[$field] = $this->mapValidationTypeToSwaggerType($rules, $controller, $method, $field);
          if ((is_string($rules) && in_array('confirmed', explode('|', $rules))) ||
            (is_array($rules) && in_array('confirmed', $rules)) &&
            strpos($field, 'password') !== false
          ) {
            $properties[$field.'_confirmation'] = ['type' => 'string'];
          }
        }
        if (is_string($rules)) {
          $rules = explode('|', $rules);
        }
        if (is_array($rules) && in_array('required', $rules, true)) {
          $requiredFields[] = $field;
        }
      } catch (\Throwable $e) {
        $this->issues[] = SwaggerGenerationException::unparsableValidation(
          $controller ?? '',
          $method ?? '',
          (string) $field,
          'Erreur inattendue lors du traitement de ce champ: '.$e->getMessage(),
          'Vérifiez la définition de cette règle de validation. Si le problème persiste, ouvrez une issue avec ce message.',
        );
      }
    }

    return [$properties, $requiredFields];
  }

  private function generateProperties($tableName, $columns, $controller = null, $method = null)
  {
    $properties = [];
    foreach ($columns as $column) {
      try {
        if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
          $type = Schema::getColumnType($tableName, $column);
          $enums = [];
          if ($type == 'enum') {
            $migrationFiles = glob(database_path('migrations/*.php'));
            $enumValues = [];

            $migrationFiles = array_filter($migrationFiles, function ($file) use ($tableName) {
              return strpos(basename($file), $tableName) !== false;
            });

            foreach ($migrationFiles as $file) {
              $content = file_get_contents($file);

              if (strpos($content, "Schema::create('$tableName'") !== false) {
                $pattern = '/\$table->enum\(([^)]*)\)/';
                if (preg_match_all($pattern, $content, $matches)) {
                  $enumArrayString = $matches[1];

                  if (is_array($enumArrayString)) {
                    foreach ($enumArrayString as $enumString) {
                      $enumValues = explode(',', $enumString, 2);
                      $columnName = trim($enumValues[0], "' ");
                      $valuesArray = isset($enumValues[1]) ? LiteralPhpEvaluator::evaluateArrayExpression($enumValues[1]) : null;

                      if ($columnName == $column) {
                        if ($valuesArray === null) {
                          $this->issues[] = SwaggerGenerationException::unparsableValidation(
                            $controller ?? '',
                            $method ?? '',
                            $column,
                            "Les valeurs de l'enum de la colonne \"$column\" ($tableName) ne sont pas un tableau littéral et n'ont pas pu être lues sans exécuter de code.",
                            "Définissez les valeurs directement dans la migration, ex: \$table->enum('$column', ['valeur_a', 'valeur_b']).",
                          );
                        } else {
                          $enums = array_merge($enums, $valuesArray);
                        }
                      }
                    }
                  }
                  break;
                }
              }
            }

            $properties[$column] = ['type' => 'string', 'enum' => $enums];
          } else {
            $properties[$column] = $this->mapColumnTypeToSwaggerType($type);
          }
        }
      } catch (\Throwable $e) {
        $this->issues[] = SwaggerGenerationException::columnIntrospectionFailed(
          $tableName,
          "Erreur inattendue en lisant la colonne \"$column\": ".$e->getMessage(),
          'Vérifiez le type de cette colonne dans la migration correspondante.',
        );
      }
    }

    return $properties;
  }
}
