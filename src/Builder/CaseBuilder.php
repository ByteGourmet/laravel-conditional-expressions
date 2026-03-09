<?php

namespace ByteGourmet\LaravelConditionalExpressions\Builder;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CaseBuilder extends Builder
{
    public function __construct(?ConnectionInterface $connection = null)
    {
        $connection ??= DB::connection();

        parent::__construct($connection);
    }

    public static function query(): static
    {
        return (new static())
            ->from('__CASEBUILDER__ as cb');
    }

    public function getConditions(): string
    {
        $sql = $this->getGrammar()->substituteBindingsIntoRawSql(
            $this->getGrammar()->compileWheres($this),
            $this->getBindings()
        );

        $result = preg_replace('/^where\s+/i', '', $sql);

        return "({$result})";
    }
}
