<?php

namespace ByteGourmet\LaravelConditionalExpressions;

use ByteGourmet\LaravelConditionalExpressions\Builder\CaseBuilder;
use ByteGourmet\LaravelConditionalExpressions\Expression\CaseExpression;

class CaseExpr
{
    public static function make(?string $column = null): CaseExpression
    {
        return CaseExpression::make($column);
    }

    public static function simple(string $column): CaseExpression
    {
        return CaseExpression::simple($column);
    }

    public static function builder(): CaseBuilder
    {
        return CaseBuilder::query();
    }
}
