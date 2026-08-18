<?php

namespace LaraSwagger\Support;

use PhpParser\Error as PhpParserError;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\ParserFactory;

/**
 * Turns a PHP AST node (or a standalone snippet of PHP source) representing
 * a literal value into a real PHP value, without ever calling `eval()`.
 *
 * Only strings, numbers, booleans, null, arrays of the above, and simple
 * concatenations are understood. Anything else (function calls, variables,
 * `new` expressions, static calls...) is refused: it is code, not data, and
 * this class never executes code.
 */
class LiteralPhpEvaluator
{
  const UNEVALUABLE = "\0__laraswagger_unevaluable__\0";

  /**
   * Parses a standalone PHP expression (e.g. "['a', 'b']") and evaluates it
   * as a literal. Returns null if it isn't parseable PHP or isn't a literal.
   *
   * @return array|null
   */
  public static function evaluateArrayExpression($phpExpr)
  {
    try {
      $ast = self::createParser()->parse('<?php return '.$phpExpr.';');
    } catch (PhpParserError $e) {
      return null;
    }

    if (! is_array($ast) || ! isset($ast[0]) || ! $ast[0] instanceof Return_ || $ast[0]->expr === null) {
      return null;
    }

    $value = self::evaluateNode($ast[0]->expr);

    return is_array($value) ? $value : null;
  }

  /**
   * @return mixed Returns self::UNEVALUABLE when the node isn't a literal.
   */
  public static function evaluateNode(Node $node)
  {
    if ($node instanceof String_) {
      return $node->value;
    }

    if ($node instanceof LNumber || $node instanceof DNumber) {
      return $node->value;
    }

    if ($node instanceof ConstFetch) {
      $name = strtolower($node->name->toString());
      if ($name === 'true') {
        return true;
      }
      if ($name === 'false') {
        return false;
      }
      if ($name === 'null') {
        return null;
      }

      return self::UNEVALUABLE;
    }

    if ($node instanceof Concat) {
      $left = self::evaluateNode($node->left);
      $right = self::evaluateNode($node->right);
      if ($left === self::UNEVALUABLE || $right === self::UNEVALUABLE) {
        return self::UNEVALUABLE;
      }

      return $left.$right;
    }

    if ($node instanceof Array_) {
      $out = [];
      foreach ($node->items as $item) {
        if (! $item instanceof ArrayItem) {
          continue;
        }
        $value = self::evaluateNode($item->value);
        if ($value === self::UNEVALUABLE) {
          return self::UNEVALUABLE;
        }

        if ($item->key !== null) {
          $key = self::evaluateNode($item->key);
          if ($key === self::UNEVALUABLE) {
            return self::UNEVALUABLE;
          }
          $out[$key] = $value;
        } else {
          $out[] = $value;
        }
      }

      return $out;
    }

    return self::UNEVALUABLE;
  }

  public static function createParser()
  {
    $factory = new ParserFactory;

    if (method_exists($factory, 'createForNewestSupportedVersion')) {
      return $factory->createForNewestSupportedVersion();
    }

    // nikic/php-parser v4 fallback (removed in v5, hence the method_exists guard above).
    // @phpstan-ignore-next-line
    return $factory->create(ParserFactory::PREFER_PHP7);
  }
}
