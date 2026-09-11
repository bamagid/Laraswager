<?php

namespace LaraSwagger\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Carries structured context about *why* a specific part of the Swagger
 * generation failed, so the console output can point at the offending
 * validation rule instead of a generic "something went wrong in trait X".
 */
class SwaggerGenerationException extends RuntimeException
{
  /** @var string|null */
  private $controller;

  /** @var string|null */
  private $method;

  /** @var string|null */
  private $field;

  /** @var mixed */
  private $rule;

  /** @var string */
  private $reason;

  /** @var string */
  private $suggestion;

  /**
   * Whether this issue leaves something genuinely undocumented (a route,
   * a field, the whole file) as opposed to a cosmetic fallback (e.g. a
   * field still gets documented, just typed as "string"). Only blocking
   * issues are surfaced to the console — a cosmetic fallback that still
   * produces complete documentation isn't worth interrupting the output for.
   *
   * @var bool
   */
  private $blocking;

  private function __construct(
    string $reason,
    string $suggestion,
    ?string $controller = null,
    ?string $method = null,
    ?string $field = null,
    $rule = null,
    ?Throwable $previous = null,
    bool $blocking = true,
  ) {
    $this->controller = $controller;
    $this->method = $method;
    $this->field = $field;
    $this->rule = $rule;
    $this->reason = $reason;
    $this->suggestion = $suggestion;
    $this->blocking = $blocking;

    parent::__construct($this->buildMessage(), 0, $previous);
  }

  public static function unparsableValidation(
    string $controller,
    string $method,
    string $field,
    string $reason,
    string $suggestion,
    bool $blocking = true,
  ): self {
    return new self($reason, $suggestion, $controller, $method, $field, null, null, $blocking);
  }

  /**
   * A single rule the generator can't type (custom rule, closure, ...).
   * Always non-blocking: the field still gets documented, defaulted to
   * "type": "string".
   */
  public static function unsupportedRule(
    string $controller,
    string $method,
    string $field,
    $rawRule,
    string $suggestion,
  ): self {
    $reason = 'Règle de validation non reconnue: '.self::describeRule($rawRule);

    return new self($reason, $suggestion, $controller, $method, $field, $rawRule, null, false);
  }

  public static function formRequestResolutionFailed(
    string $formRequestClass,
    string $controller,
    string $method,
    Throwable $previous,
    string $suggestion,
  ): self {
    $reason = "Impossible d'instancier ou d'appeler rules() sur le FormRequest {$formRequestClass}: ".$previous->getMessage();

    return new self($reason, $suggestion, $controller, $method, null, null, $previous);
  }

  public static function routeIntrospectionFailed(string $uri, string $reason, string $suggestion): self
  {
    return new self($reason, $suggestion, null, null, $uri);
  }

  public static function columnIntrospectionFailed(string $table, string $reason, string $suggestion): self
  {
    return new self($reason, $suggestion, null, null, $table);
  }

  public function blocking(): bool
  {
    return $this->blocking;
  }

  public function controller(): ?string
  {
    return $this->controller;
  }

  public function method(): ?string
  {
    return $this->method;
  }

  public function field(): ?string
  {
    return $this->field;
  }

  /**
   * The raw rule value that triggered this issue (a rule string, or the
   * offending object/array), when applicable.
   *
   * @return mixed
   */
  public function rule()
  {
    return $this->rule;
  }

  public function reason(): string
  {
    return $this->reason;
  }

  public function suggestion(): string
  {
    return $this->suggestion;
  }

  /**
   * A single-line "Controller::method" label, or "-" when not applicable.
   */
  public function actionLabel(): string
  {
    if ($this->controller === null) {
      return '-';
    }

    $short = class_basename($this->controller);

    return $this->method ? "{$short}::{$this->method}" : $short;
  }

  private function buildMessage(): string
  {
    $location = $this->controller
      ? ($this->method ? "{$this->controller}::{$this->method}" : $this->controller)
      : null;

    $parts = [];
    if ($location) {
      $parts[] = "[{$location}]";
    }
    if ($this->field) {
      $parts[] = "champ \"{$this->field}\"";
    }
    $parts[] = $this->reason;
    $parts[] = "Recommandation: {$this->suggestion}";

    return implode(' — ', $parts);
  }

  private static function describeRule($rule): string
  {
    if (is_string($rule)) {
      return $rule;
    }

    if (is_object($rule)) {
      return get_class($rule);
    }

    return var_export($rule, true);
  }
}
