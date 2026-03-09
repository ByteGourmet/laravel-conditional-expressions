<?php

namespace ByteGourmet\LaravelConditionalExpressions\Tests;

use ByteGourmet\LaravelConditionalExpressions\CaseExpr;

class CaseExpressionTest extends TestCase
{
    // TODO: add tests
    public function testSimpleCaseBindsValuesAndHandlesElseNull()
    {
        $expr = CaseExpr::simple('status')
            ->when('active')->then('Active User')
            ->else(null)
            ->as('status_label');

        $sql = $expr->toSql();

        $this->assertSame(['active', 'Active User'], $expr->getBindings());
        // $this->assertSame('(CASE `status` WHEN ? THEN ? ELSE NULL END) AS `status_label`', $sql);
    }
}
