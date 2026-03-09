<?php

namespace ByteGourmet\LaravelConditionalExpressions\Expression;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Expression as BaseExpression;
use Illuminate\Database\Grammar;
use Illuminate\Support\Facades\DB;
use LogicException;
use ByteGourmet\LaravelConditionalExpressions\Builder\CaseBuilder;

/**
 * Class CaseExpression
 *
 * Fluent CASE WHEN expression builder for Laravel.
 * Supports both simple and searched CASE, nested CASE, and bindings.
 *
 * @package ByteGourmet\LaravelConditionalExpressions
 */
class CaseExpression extends BaseExpression
{
    /**
     * The database query grammar instance.
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    public $grammar;

    protected array $whenThen = [];
    protected mixed $else = null;
    protected ?string $column = null;
    protected ?string $alias = null;
    protected bool $hasElse = false;

    private function __construct(?string $column = null)
    {
        $this->column = $column;
        $this->grammar = DB::getQueryGrammar();
    }

    public static function make(?string $column = null): static
    {
        return new static($column);
    }

    public static function simple(string $column): static
    {
        return new static($column);
    }

    public function when(
        string|CaseBuilder|Expression $column,
        ?string $operator = null,
        mixed $value = null
    ): static {
        $this->whenThen[] = compact('column', 'operator', 'value');

        return $this;
    }

    /**
     * Set the THEN value for the last WHEN clause.
     * Accepts scalar values or another CaseExpression for nesting.
     */
    public function then(mixed $result): static
    {
        $last = array_key_last($this->whenThen);

        if ($last === null) {
            throw new LogicException('Cannot call then() before any when() clause.');
        }

        $this->whenThen[$last]['then'] = $result;

        return $this;
    }

    /**
     * Set the ELSE value.
     * Accepts scalar values, column names, DB raw expressions, or another CaseExpression for nesting.
     */
    public function else(mixed $result): static
    {
        $this->else = $result;
        $this->hasElse = true;

        return $this;
    }

    public function as(string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Get the value of the expression.
     *
     * @param  \Illuminate\Database\Grammar  $grammar
     * @return string
     */
    public function getValue(Grammar $grammar)
    {
        $sql = $this->column ? "CASE {$grammar->wrap($this->column)}" : "CASE";
        $isSimple = $this->column !== null;

        foreach ($this->whenThen as $w) {
            $then = $w['then'] ?? null;

            if (! $then) {
                $clauseDump = json_encode($w);

                throw new LogicException(
                    "Missing required then() for when() clause: $clauseDump"
                );
            }

            $column = $w['column'];
            $operator = $w['operator'];
            $value = $w['value'];

            if ($isSimple) {
                // Simple CASE: the provided argument is a value, not a column
                if ($column instanceof Expression) {
                    $sql .= " WHEN {$column->getValue($grammar)}";
                } else {
                    $sql .= " WHEN ?";
                }
            } else {
                if ($column instanceof CaseBuilder) {
                    $column = $column->getConditions();
                } elseif ($column instanceof Expression) {
                    $column = $column->getValue($grammar);
                } else {
                    $column = $grammar->wrap($column);
                }

                if ($operator !== null) {
                    if ($value instanceof Expression) {
                        $valueSql = $value->getValue($grammar);
                        $sql .= " WHEN {$column} {$operator} {$valueSql}";
                    } else {
                        $sql .= " WHEN {$column} {$operator} ?";
                    }
                } else {
                    $sql .= " WHEN {$column}";
                }
            }

            $sql .= " THEN " . $this->formatValue($then, $grammar);
        }

        if ($this->hasElse) {
            $else = $this->else;

            $append = $else === null ? 'NULL' : $this->formatValue($else, $grammar);

            $sql .= " ELSE {$append}";
        }

        $sql .= " END";

        $sql = "({$sql})";

        if ($this->alias !== null) {
            $sql .= " AS {$grammar->wrap($this->alias)}";
        }

        return $this->grammar->substituteBindingsIntoRawSql(
            $sql,
            $this->getBindings()
        );
    }

    /**
     * Flatten bindings, including nested CASE expressions.
     */
    public function getBindings(): array
    {
        $bindings = [];
        $isSimple = $this->column !== null;
        foreach ($this->whenThen as $w) {
            if ($isSimple) {
                if (! ($w['column'] instanceof Expression)) {
                    $bindings[] = $w['column'];
                }
            } elseif ($w['operator'] !== null && array_key_exists('value', $w)) {
                if (! ($w['value'] instanceof Expression)) {
                    $bindings[] = $w['value'];
                }
            }

            $then = $w['then'];
            if ($then instanceof self) {
                $bindings = array_merge($bindings, $then->getBindings());
            } elseif ($then instanceof Expression) {
                // Raw expressions don't need bindings
            } elseif (is_string($then) && $this->isColumnReference($then)) {
                // Column references don't need bindings
            } else {
                $bindings[] = $then;
            }
        }

        if ($this->hasElse) {
            $else = $this->else;

            if ($else instanceof self) {
                $bindings = array_merge($bindings, $else->getBindings());
            } elseif ($else instanceof Expression) {
                // Raw expressions don't need bindings
            } elseif (is_string($else) && $this->isColumnReference($else)) {
                // Column references don't need bindings
            } elseif ($else !== null) {
                $bindings[] = $else;
            }
        }

        return $bindings;
    }

    public function toSql(): string
    {
        return $this->getValue(DB::getQueryGrammar());
    }

    public function dump()
    {
        dump($this->toSql());
    }

    public function dd()
    {
        dd($this->toSql());
    }

    /**
     * Format a value for SQL output.
     * Handles nested CaseExpression, DB raw expressions, column names, and scalar values.
     */
    protected function formatValue(mixed $value, Grammar $grammar): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_string($value) && $this->isColumnReference($value)) {
            return $grammar->wrap($value);
        }

        if ($value instanceof self) {
            return $value->getValue($grammar);
        }

        if ($value instanceof Expression) {
            return $value->getValue($grammar);
        }

        return '?';
    }

    /**
     * Check if a string is a column reference rather than a scalar value.
     * Only treats strings as column references if they contain dots (table.column)
     * or backticks (explicit column reference). Simple strings are treated as scalar bindings.
     */
    protected function isColumnReference(string $value): bool
    {
        // Only treat as column reference if it contains dots or backticks
        // This ensures simple strings like 'unknown' are treated as scalar bindings
        return strpos($value, '`') !== false || strpos($value, '.') !== false;
    }
}
