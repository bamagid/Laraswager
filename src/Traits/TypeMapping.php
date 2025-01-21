<?php

namespace LaraSwagger\Traits;

use Illuminate\Support\Facades\Schema;

trait TypeMapping
{
  private function mapColumnTypeToSwaggerType($type)
  {
    $swaggerTypeMap = [
      'integer' => ['type' => 'integer', 'format' => 'int32'],
      'bigint' => ['type' => 'integer', 'format' => 'int64'],
      'float' => ['type' => 'number', 'format' => 'float'],
      'double' => ['type' => 'number', 'format' => 'double'],
      'string' => ['type' => 'string'],
      'text' => ['type' => 'string'],
      'boolean' => ['type' => 'boolean'],
      'date' => ['type' => 'string', 'format' => 'date'],
      'datetime' => ['type' => 'string', 'format' => 'date-time'],
      'timestamp' => ['type' => 'string', 'format' => 'date-time'],
      'binary' => ['type' => 'string', 'format' => 'binary'],
      'array' => ['type' => 'array', 'items' => ['type' => 'string']],
      'json' => ['type' => 'object']
    ];

    return $swaggerTypeMap[$type] ?? ['type' => 'string'];
  }

  private function generatePropertiesWithExceptionHandling($tableName, $columns)
  {
    $properties = [];

    foreach ($columns as $column) {
      try {
        if ($column != "id" && $column != "created_at" && $column != "updated_at") {
          $type = Schema::getColumnType($tableName, $column);
          $properties[$column] = $this->mapColumnTypeToSwaggerType($type);
        }
      } catch (\Exception $e) {
        $this->error("Error processing column: $column", [
          'table' => $tableName,
          'exception' => $e->getMessage()
        ]);

        // Provide a default value in case of error
        $properties[$column] = ['type' => 'string', 'description' => 'Error processing this column.'];
      }
    }

    return $properties;
  }

  private function mapValidationTypeToSwaggerTypeWithExceptionHandling($rules)
  {
    try {
      if (is_string($rules)) {
        $rules = explode('|', $rules);
      }

      $type = 'string';
      $format = null;
      $enum = null;

      foreach ($rules as $rule) {
        if (strpos($rule, 'in:') === 0) {
          $enum = explode(',', substr($rule, 3));
        }
        if ($rule === 'integer') {
          $type = 'integer';
          $format = 'int32';
        } elseif ($rule === 'numeric') {
          $type = 'number';
          $format = 'float';
        } elseif ($rule === 'boolean') {
          $type = 'boolean';
        } elseif ($rule === 'date') {
          $type = 'string';
          $format = 'date';
        } elseif ($rule === 'file' || $rule === 'image') {
          $type = 'string';
          $format = 'binary';
        } elseif ($rule === 'array') {
          $type = 'array';
        }
      }

      $swaggerType = ['type' => $type];

      if ($format) {
        $swaggerType['format'] = $format;
      }

      if ($enum) {
        $swaggerType['enum'] = $enum;
      }

      return $swaggerType;
    } catch (\Exception $e) {
      $this->error("Error processing validation rules", [
        'rules' => $rules,
        'exception' => $e->getMessage()
      ]);

      // Return a default type in case of an exception
      return ['type' => 'string', 'description' => 'Error processing validation rules.'];
    }
  }
}
