<?php

namespace LaraSwagger\Traits;

use LaraSwagger\Exceptions\SwaggerGenerationException;

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
      'json' => ['type' => 'object'],
    ];

    return $swaggerTypeMap[$type] ?? ['type' => 'string'];
  }

  /**
   * Rules that unambiguously determine the OpenAPI base type of a field.
   * When several rules for the same field appear here, the last one wins.
   */
  private function validationTypeTable()
  {
    return [
      // boolean
      'accepted' => ['type' => 'boolean'],
      'accepted_if' => ['type' => 'boolean'],
      'declined' => ['type' => 'boolean'],
      'declined_if' => ['type' => 'boolean'],
      'boolean' => ['type' => 'boolean'],

      // numeric
      'integer' => ['type' => 'integer', 'format' => 'int32'],
      'numeric' => ['type' => 'number', 'format' => 'float'],
      'decimal' => ['type' => 'number'],
      'digits' => ['type' => 'integer'],
      'digits_between' => ['type' => 'integer'],
      'max_digits' => ['type' => 'integer'],
      'min_digits' => ['type' => 'integer'],
      'multiple_of' => ['type' => 'number'],

      // string
      'string' => ['type' => 'string'],
      'alpha' => ['type' => 'string'],
      'alpha_dash' => ['type' => 'string'],
      'alpha_num' => ['type' => 'string'],
      'ascii' => ['type' => 'string'],
      'current_password' => ['type' => 'string', 'format' => 'password'],
      'password' => ['type' => 'string', 'format' => 'password'],
      'lowercase' => ['type' => 'string'],
      'uppercase' => ['type' => 'string'],
      'timezone' => ['type' => 'string'],
      'email' => ['type' => 'string', 'format' => 'email'],
      'url' => ['type' => 'string', 'format' => 'uri'],
      'active_url' => ['type' => 'string', 'format' => 'uri'],
      'uuid' => ['type' => 'string', 'format' => 'uuid'],
      'ulid' => ['type' => 'string'],
      'ip' => ['type' => 'string'],
      'ipv4' => ['type' => 'string', 'format' => 'ipv4'],
      'ipv6' => ['type' => 'string', 'format' => 'ipv6'],
      'mac_address' => ['type' => 'string'],
      'json' => ['type' => 'string'],
      'hex_color' => ['type' => 'string'],

      // date
      'date' => ['type' => 'string', 'format' => 'date'],
      'date_equals' => ['type' => 'string', 'format' => 'date'],
      'after' => ['type' => 'string', 'format' => 'date'],
      'after_or_equal' => ['type' => 'string', 'format' => 'date'],
      'before' => ['type' => 'string', 'format' => 'date'],
      'before_or_equal' => ['type' => 'string', 'format' => 'date'],

      // array
      'array' => ['type' => 'array'],
      'list' => ['type' => 'array'],
      'array_keys' => ['type' => 'array'],
      'required_array_keys' => ['type' => 'array'],
      'contains' => ['type' => 'array'],
      'doesnt_contain' => ['type' => 'array'],
      'distinct' => ['type' => 'array'],
      'in_array' => ['type' => 'array'],
      'in_array_keys' => ['type' => 'array'],

      // file
      'file' => ['type' => 'string', 'format' => 'binary'],
      'image' => ['type' => 'string', 'format' => 'binary'],
      'dimensions' => ['type' => 'string', 'format' => 'binary'],
      'encoding' => ['type' => 'string', 'format' => 'binary'],
      'extensions' => ['type' => 'string', 'format' => 'binary'],
      'mimes' => ['type' => 'string', 'format' => 'binary'],
      'mimetypes' => ['type' => 'string', 'format' => 'binary'],
    ];
  }

  /**
   * Rules that don't set a type by themselves but take a parameter used to
   * constrain the schema of whatever base type was already resolved.
   */
  private function constraintRuleNames()
  {
    return [
      'between', 'size', 'max', 'min', 'in', 'not_in', 'regex', 'not_regex',
      'date_format', 'digits', 'digits_between', 'mimes', 'mimetypes',
    ];
  }

  /**
   * Known Laravel validation rules that don't affect the generated schema
   * at all (presence/conditional rules, meta rules, DB lookups...).
   */
  private function nonTypeAffectingRules()
  {
    return [
      'bail', 'sometimes', 'nullable', 'filled', 'confirmed', 'same', 'different',
      'missing', 'missing_if', 'missing_unless', 'missing_with', 'missing_with_all',
      'present', 'present_if', 'present_unless', 'present_with', 'present_with_all',
      'prohibited', 'prohibited_if', 'prohibited_if_accepted', 'prohibited_if_declined',
      'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted',
      'required_if_declined', 'required_unless', 'required_with', 'required_with_all',
      'required_without', 'required_without_all', 'exclude', 'exclude_if', 'exclude_unless',
      'exclude_with', 'exclude_without', 'exists', 'unique', 'gt', 'gte', 'lt', 'lte',
      'enum', 'any_of', 'starts_with', 'ends_with', 'doesnt_start_with', 'doesnt_end_with',
    ];
  }

  /**
   * @param  string|array  $rules
   * @param  string|null  $controller
   * @param  string|null  $method
   * @param  string|null  $field
   */
  private function mapValidationTypeToSwaggerType($rules, $controller = null, $method = null, $field = null)
  {
    $ruleList = is_string($rules) ? explode('|', $rules) : (array) $rules;

    $objectEnum = null;
    $ruleList = $this->normalizeRuleObjects($ruleList, $controller, $method, $field, $objectEnum);

    $typeTable = $this->validationTypeTable();
    $known = array_merge(
      array_keys($typeTable),
      $this->constraintRuleNames(),
      $this->nonTypeAffectingRules(),
    );

    $schema = ['type' => 'string'];
    $enum = $objectEnum;
    $pattern = null;
    $notes = [];

    // Pass 1: resolve the base type/format.
    foreach ($ruleList as $rule) {
      $name = $this->ruleName($rule);
      if (isset($typeTable[$name])) {
        $schema = array_merge(['type' => 'string'], $typeTable[$name]);
      }
    }

    // Pass 2: apply constraints on top of the resolved base type.
    foreach ($ruleList as $rule) {

      $name = $this->ruleName($rule);
      $params = $this->ruleParameters($rule);

      switch ($name) {
        case 'in':
          $enum = array_map([$this, 'unquoteRuleValue'], $params);
          break;
        case 'not_in':
          $notes[] = 'Exclu: '.implode(', ', array_map('trim', $params));
          break;
        case 'date_format':
          if (isset($params[0])) {
            $schema['format'] = $params[0];
          }
          break;
        case 'regex':
          if (isset($params[0])) {
            $pattern = $params[0];
          }
          break;
        case 'multiple_of':
          if (isset($params[0]) && is_numeric($params[0])) {
            $schema['multipleOf'] = $params[0] + 0;
          }
          break;
        case 'between':
          $this->applyRange($schema, $params[0] ?? null, $params[1] ?? null);
          break;
        case 'size':
          $this->applyRange($schema, $params[0] ?? null, $params[0] ?? null);
          break;
        case 'max':
          $this->applyRange($schema, null, $params[0] ?? null);
          break;
        case 'min':
          $this->applyRange($schema, $params[0] ?? null, null);
          break;
        case 'digits':
          if (isset($params[0])) {
            $notes[] = "Doit contenir exactement {$params[0]} chiffre(s)";
          }
          break;
        case 'digits_between':
          if (isset($params[0], $params[1])) {
            $notes[] = "Doit contenir entre {$params[0]} et {$params[1]} chiffres";
          }
          break;
        case 'mimes':
        case 'mimetypes':
          if (! empty($params)) {
            $notes[] = 'Types de fichiers autorisés: '.implode(', ', $params);
          }
          break;
      }

      if (! in_array($name, $known, true)) {
        $this->issues[] = SwaggerGenerationException::unsupportedRule(
          $controller ?? '',
          $method ?? '',
          $field ?? '?',
          $rule,
          "Règle de validation inconnue du générateur. Si c'est une règle personnalisée, son type ne peut pas "
            .'être deviné automatiquement — le champ est documenté en "string" par défaut. Ouvrez une '
            .'issue si cette règle devrait être reconnue nativement.',
        );
      }
    }

    if ($enum !== null) {
      $schema['enum'] = $enum;
    }
    if ($pattern !== null) {
      $schema['pattern'] = $pattern;
    }
    if (! empty($notes)) {
      $schema['description'] = implode('. ', $notes);
    }

    return $schema;
  }

  private function ruleName($rule)
  {
    return strpos($rule, ':') !== false ? substr($rule, 0, strpos($rule, ':')) : $rule;
  }

  private function ruleParameters($rule)
  {
    if (strpos($rule, ':') === false) {
      return [];
    }

    return explode(',', substr($rule, strpos($rule, ':') + 1));
  }

  private function applyRange(array &$schema, $min, $max)
  {
    $type = $schema['type'];

    if ($type === 'integer' || $type === 'number') {
      if ($min !== null && is_numeric($min)) {
        $schema['minimum'] = $min + 0;
      }
      if ($max !== null && is_numeric($max)) {
        $schema['maximum'] = $max + 0;
      }

      return;
    }

    if ($type === 'array') {
      if ($min !== null && is_numeric($min)) {
        $schema['minItems'] = (int) $min;
      }
      if ($max !== null && is_numeric($max)) {
        $schema['maxItems'] = (int) $max;
      }

      return;
    }

    // string (including binary/file): length in characters, size in KB for files.
    if ($min !== null && is_numeric($min)) {
      $schema['minLength'] = (int) $min;
    }
    if ($max !== null && is_numeric($max)) {
      $schema['maxLength'] = (int) $max;
    }
  }

  private function reportUnsupportedRuleValue($rule, $controller, $method, $field)
  {
    $this->issues[] = SwaggerGenerationException::unsupportedRule(
      $controller ?? '',
      $method ?? '',
      $field ?? '?',
      $rule,
      'Cette règle est un objet qui ne peut pas être converti en chaîne (ex: une closure, ou une classe de '
        .'règle personnalisée sans __toString()). Son type ne peut pas être déduit automatiquement — le champ '
        .'est documenté en "string" par défaut.',
    );
  }

  /**
   * Rule objects (Rule::in(), Rule::exists(), custom Rule classes, ...) are
   * real PHP objects here — this only runs for FormRequest-sourced rules,
   * never for statically-extracted inline rules, so calling their own
   * __toString() doesn't run any of *our* code, only Laravel's own rule
   * formatting. `Rule::enum()` is special-cased since it has no
   * __toString() but its allowed values can be read via reflection.
   *
   * @param  array<int, mixed>  $ruleList
   * @return array<int, string>
   */
  private function normalizeRuleObjects(array $ruleList, $controller, $method, $field, &$objectEnum)
  {
    $normalized = [];

    foreach ($ruleList as $rule) {
      if (is_string($rule)) {
        $normalized[] = $rule;

        continue;
      }

      if (! is_object($rule)) {
        $this->reportUnsupportedRuleValue($rule, $controller, $method, $field);

        continue;
      }

      if (is_a($rule, 'Illuminate\Validation\Rules\Enum')) {
        $enumInfo = $this->describeEnumRule($rule);
        if ($enumInfo !== null) {
          $normalized[] = $enumInfo['type'];
          $objectEnum = $enumInfo['values'];

          continue;
        }
      }

      // Email, File and ImageFile implement the Rule contract (not Stringable),
      // so they don't fall into the __toString() path below — but their type
      // is unambiguous, so they're special-cased the same way as Enum.
      if (is_a($rule, 'Illuminate\Validation\Rules\Email')) {
        $normalized[] = 'email';

        continue;
      }
      if (is_a($rule, 'Illuminate\Validation\Rules\File')) {
        $normalized[] = 'file';

        continue;
      }

      if (method_exists($rule, '__toString')) {
        try {
          $normalized[] = (string) $rule;

          continue;
          // A third-party Rule's __toString() could still throw at runtime even
          // though static analysis can't see a path that does.
          // @phpstan-ignore-next-line
        } catch (\Throwable $e) {
          // Fall through to the "unsupported" report below.
        }
      }

      $this->reportUnsupportedRuleValue($rule, $controller, $method, $field);
    }

    return $normalized;
  }

  /**
   * @return array{type: string, values: array<int, mixed>}|null
   */
  private function describeEnumRule($rule)
  {
    $property = new \ReflectionProperty($rule, 'type');
    $property->setAccessible(true);
    $enumClass = $property->getValue($rule);

    if (! is_string($enumClass) || ! enum_exists($enumClass)) {
      return null;
    }

    $cases = $enumClass::cases();
    if (is_a($enumClass, 'BackedEnum', true)) {
      $values = array_map(static function ($case) {
        // is_a($enumClass, BackedEnum::class, true) above guarantees ->value here.
        // @phpstan-ignore-next-line
        return $case->value;
      }, $cases);
      $type = is_int($values[0] ?? null) ? 'integer' : 'string';
    } else {
      $values = array_map(static function ($case) {
        return $case->name;
      }, $cases);
      $type = 'string';
    }

    return ['type' => $type, 'values' => $values];
  }

  private function unquoteRuleValue($value)
  {
    $value = trim($value);
    if (strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"') {
      $value = str_replace('""', '"', substr($value, 1, -1));
    }

    return $value;
  }
}
