<?php

namespace LaraSwagger\Traits;

use Illuminate\Support\Facades\Schema;

trait SwaggerPropertiesTrait
{

  private function generatePropertiesFromValidations($validations)
  {
    $properties = [];
    $requiredFields = [];

    foreach ($validations as $field => $rules) {
      if (strpos($field, '.*') !== false) {
        $baseField = explode('.*', $field)[0];

        if (!isset($properties[$baseField])) {
          $properties[$baseField] = [
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => []
            ]
          ];
        }

        $subField = explode('.*.', $field)[1] ?? null;
        if ($subField) {
          $properties[$baseField]['items']['type'] = "object";
          $properties[$baseField]['items']['properties'][$subField] = $this->mapValidationTypeToSwaggerType($rules);
        } else {
          $properties[$baseField]['items'] = $this->mapValidationTypeToSwaggerType($rules);
        }
      } else {
        $properties[$field] = $this->mapValidationTypeToSwaggerType($rules);
        if ((is_string($rules) && in_array('confirmed', explode('|', $rules))) ||
          (is_array($rules) && in_array('confirmed', $rules)) &&
          strpos($field, 'password') !== false
        ) {
          $properties[$field . '_confirmation'] = ['type' => 'string'];
        }
      }
      if (is_string($rules)) {
        $rules = explode('|', $rules);
      }
      if (in_array('required', $rules)) {
        $requiredFields[] = $field;
      }
    }

    return [$properties, $requiredFields];
  }

  private function generateProperties($tableName, $columns)
  {
    $properties = [];
    foreach ($columns as $column) {
      if ($column != "id" && $column != "created_at" && $column != "updated_at") {
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
              $pattern = '/\$table->enum\(([^;]*)\)/';
              if (preg_match_all($pattern, $content, $matches)) {
                $enumArrayString = $matches[1];

                if (is_array($enumArrayString)) {
                  foreach ($enumArrayString as $enumString) {
                    $enumValues = explode(',', $enumString, 2);
                    $columnName = trim($enumValues[0], "' ");
                    $valuesArray = eval('return ' . $enumValues[1] . ';');
                    if ($columnName == $column) {
                      $enums = array_merge($enums, $valuesArray);
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
    }
    return $properties;
  }
}
