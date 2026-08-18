<?php

namespace LaraSwagger\Support;

use LaraSwagger\Exceptions\SwaggerGenerationException;
use PhpParser\Error as PhpParserError;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use ReflectionMethod;

/**
 * Reads `$request->validate([...])`, `Validator::make($data, [...])` and
 * `$rules = [...]` out of a controller method by parsing it into a real
 * PHP AST (nikic/php-parser) and evaluating only literal values.
 *
 * Nothing here ever executes code extracted from the controller: any
 * expression that isn't a literal (string/number/bool/null/array or a
 * concatenation of those) is reported as an issue instead of being
 * evaluated, so a dynamic rule can never crash generation or run
 * arbitrary code.
 */
class ValidationRuleExtractor
{
  /** @var SwaggerGenerationException[] */
  private $issues = [];

  /**
   * @return array<string, mixed> Field => rule definition (string or array).
   */
  public function extractFromMethod(ReflectionMethod $methodReflection, string $controller, string $method): array
  {
    $file = $methodReflection->getFileName();
    if ($file === false) {
      return [];
    }

    $code = file_get_contents($file);
    if ($code === false) {
      return [];
    }

    try {
      $ast = LiteralPhpEvaluator::createParser()->parse($code);
    } catch (PhpParserError $e) {
      $this->issues[] = SwaggerGenerationException::unparsableValidation(
        $controller,
        $method,
        '-',
        "Le fichier source n'a pas pu être analysé: {$e->getMessage()}",
        'Vérifiez la syntaxe du contrôleur (php -l sur le fichier).',
      );

      return [];
    }

    if ($ast === null) {
      return [];
    }

    $finder = new NodeFinder;
    /** @var ClassMethod|null $methodNode */
    $methodNode = $finder->findFirst($ast, function (Node $node) use ($method) {
      return $node instanceof ClassMethod && $node->name->toString() === $method;
    });

    if ($methodNode === null || $methodNode->stmts === null) {
      return [];
    }

    $arrayNode = $this->findRulesArray($methodNode, $finder, $controller, $method);
    if ($arrayNode === null) {
      return [];
    }

    return $this->evaluateRulesArray($arrayNode, $controller, $method);
  }

  /**
   * @return SwaggerGenerationException[]
   */
  public function issues(): array
  {
    return $this->issues;
  }

  private function findRulesArray(ClassMethod $methodNode, NodeFinder $finder, string $controller, string $method): ?Array_
  {
    /** @var MethodCall|null $validateCall */
    $validateCall = $finder->findFirst($methodNode->stmts, function (Node $node) {
      return $node instanceof MethodCall
        && $node->name instanceof Node\Identifier
        && $node->name->toString() === 'validate';
    });

    if ($validateCall !== null && isset($validateCall->args[0])) {
      return $this->resolveArrayNodeOrReport(
        $validateCall->args[0]->value,
        $methodNode,
        $finder,
        $controller,
        $method,
      );
    }

    /** @var StaticCall|null $validatorMake */
    $validatorMake = $finder->findFirst($methodNode->stmts, function (Node $node) {
      return $node instanceof StaticCall
        && $node->class instanceof Node\Name
        && strtolower($node->class->getLast()) === 'validator'
        && $node->name instanceof Node\Identifier
        && $node->name->toString() === 'make';
    });

    if ($validatorMake !== null && isset($validatorMake->args[1])) {
      return $this->resolveArrayNodeOrReport(
        $validatorMake->args[1]->value,
        $methodNode,
        $finder,
        $controller,
        $method,
      );
    }

    /** @var Assign|null $rulesAssign */
    $rulesAssign = $finder->findFirst($methodNode->stmts, function (Node $node) {
      return $node instanceof Assign
        && $node->var instanceof Variable
        && $node->var->name === 'rules'
        && $node->expr instanceof Array_;
    });

    if ($rulesAssign !== null) {
      /** @var Array_ $expr */
      $expr = $rulesAssign->expr;

      return $expr;
    }

    return null;
  }

  private function resolveArrayNodeOrReport(
    Node $node,
    ClassMethod $methodNode,
    NodeFinder $finder,
    string $controller,
    string $method,
  ): ?Array_ {
    $resolved = $this->resolveArrayNode($node, $methodNode, $finder);

    if ($resolved === null) {
      $this->issues[] = SwaggerGenerationException::unparsableValidation(
        $controller,
        $method,
        '-',
        'Un appel de validation a été trouvé, mais son tableau de règles est construit dynamiquement '
          .'('.$this->describeNode($node).') et ne peut pas être lu sans exécuter de code.',
        'Passez un tableau littéral directement, ou utilisez un FormRequest pour cette validation.',
      );
    }

    return $resolved;
  }

  private function resolveArrayNode(Node $node, ClassMethod $methodNode, NodeFinder $finder): ?Array_
  {
    if ($node instanceof Array_) {
      return $node;
    }

    if ($node instanceof Variable && is_string($node->name)) {
      $name = $node->name;
      /** @var Assign|null $assign */
      $assign = $finder->findFirst($methodNode->stmts, function (Node $n) use ($name) {
        return $n instanceof Assign
          && $n->var instanceof Variable
          && $n->var->name === $name
          && $n->expr instanceof Array_;
      });

      if ($assign !== null) {
        /** @var Array_ $expr */
        $expr = $assign->expr;

        return $expr;
      }
    }

    return null;
  }

  /**
   * @return array<string, mixed>
   */
  private function evaluateRulesArray(Array_ $array, string $controller, string $method): array
  {
    $rules = [];

    foreach ($array->items as $item) {
      if (! $item instanceof ArrayItem || $item->key === null) {
        continue;
      }

      $field = LiteralPhpEvaluator::evaluateNode($item->key);
      if (! is_string($field)) {
        continue;
      }

      $rules[$field] = $this->evaluateRuleValue($item->value, $controller, $method, $field);
    }

    return $rules;
  }

  /**
   * Evaluates the rule definition for a single field: a pipe string, or an
   * array of individual rules. Individual rules that can't be evaluated
   * statically (Rule objects, function calls, variables) are dropped with
   * a reported issue instead of aborting the whole field.
   */
  private function evaluateRuleValue(Node $node, string $controller, string $method, string $field)
  {
    if ($node instanceof Array_) {
      $items = [];
      foreach ($node->items as $item) {
        if (! $item instanceof ArrayItem) {
          continue;
        }

        $value = LiteralPhpEvaluator::evaluateNode($item->value);
        if ($value === LiteralPhpEvaluator::UNEVALUABLE) {
          $this->issues[] = SwaggerGenerationException::unsupportedRule(
            $controller,
            $method,
            $field,
            $this->describeNode($item->value),
            "Cette règle n'est pas une valeur littérale et ne peut pas être lue sans exécuter de code. "
              .'Remplacez-la par une chaîne (ex: "required|string"), ou utilisez un FormRequest '
              .'si la règle doit rester dynamique.',
          );

          continue;
        }

        $items[] = $value;
      }

      return $items;
    }

    $value = LiteralPhpEvaluator::evaluateNode($node);
    if ($value === LiteralPhpEvaluator::UNEVALUABLE) {
      $this->issues[] = SwaggerGenerationException::unsupportedRule(
        $controller,
        $method,
        $field,
        $this->describeNode($node),
        "La définition de cette règle n'est pas une valeur littérale (chaîne ou tableau) et ne peut pas être "
          .'lue sans exécuter de code. Utilisez une chaîne/tableau littéral, ou déplacez cette validation '
          .'dans un FormRequest.',
      );

      return 'string';
    }

    return $value;
  }

  private function describeNode(Node $node): string
  {
    if ($node instanceof StaticCall) {
      return 'appel statique (ex: Rule::in(...))';
    }
    if ($node instanceof MethodCall) {
      return 'appel de méthode';
    }
    if ($node instanceof Expr\New_) {
      return "instanciation d'objet (new ...)";
    }
    if ($node instanceof Expr\FuncCall) {
      return 'appel de fonction';
    }
    if ($node instanceof Variable) {
      return 'variable';
    }

    return (new \ReflectionClass($node))->getShortName();
  }
}
