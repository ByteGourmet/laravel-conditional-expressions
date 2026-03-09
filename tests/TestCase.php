<?php

namespace ByteGourmet\LaravelConditionalExpressions\Tests;

use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use WithWorkbench;

    // /**
    //  * Define environment setup.
    //  *
    //  * @param  \Illuminate\Foundation\Application  $app
    //  * @return void
    //  */
    // protected function defineEnvironment($app)
    // {
    //     // Setup default database to use sqlite :memory:
    //     tap($app['config'], function (Repository $config) {
    //         $config->set('database.default', 'testbench');
    //         // $config->set('database.connections.testbench', [
    //         //     'driver'   => 'sqlite',
    //         //     'database' => ':memory:',
    //         //     'prefix'   => '',
    //         // ]);
    //     });
    // }
}
