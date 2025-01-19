<?php

namespace LaraSwagger\Traits;

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
      'date' => [
        'type' => 'string',
        'format' => 'date'
      ],
      'datetime' => ['type' => 'string', 'format' => 'date-time'],
      'timestamp' => ['type' => 'string', 'format' => 'date-time'],
      'binary' => ['type' => 'string', 'format' => 'binary'],
      'array' => ['type' => 'array', 'items' => ['type' => 'string']],
      'json' => ['type' => 'object']
    ];
    return $swaggerTypeMap[$type] ?? ['type' => 'string'];
  }
  private function mapValidationTypeToSwaggerType($rules)
  {
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
      if (strpos($rule, 'in_array:') === 0) {
        $enum = explode(',', substr($rule, 9));
      }
      if ($rule === 'integer') {
        $type = 'integer';
        $format = 'int32';
      }
      if ($rule === 'numeric') {
        $type = 'number';
        $format = 'float';
      }
      if ($rule === 'boolean') {
        $type = 'boolean';
      }
      if ($rule === 'date') {
        $type = 'string';
        $format = 'date';
      }
      if ($rule === 'file' || $rule === 'image' || $rule === 'mimes') {
        $type = 'string';
        $format = 'binary';
      }
      if ($rule === 'array') {
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
  }
}
