<?php

declare(strict_types=1);

namespace ByteGourmet\LaravelConditionalExpressions\Providers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\ServiceProvider;
use ByteGourmet\LaravelConditionalExpressions\Expression\CaseExpression;

class CaseBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerSelectCaseMacro();
    }

    protected function registerSelectCaseMacro(): void
    {
        $macro = function (string $alias, callable $callback) {
            $case = CaseExpression::make();
            $callback($case);

            /** @var QueryBuilder $this */
            $grammar = $this->getGrammar();

            return $this->selectRaw(
                $case->getValue($grammar),
                $case->getBindings()
            );
        };

        QueryBuilder::macro('selectCase', $macro);
        EloquentBuilder::macro('selectCase', $macro);
        JoinClause::macro('selectCase', $macro);
    }
}
