<?php

namespace App\Library\Backticks;

use PHPUnit\Framework\TestCase;

class BackticksTest extends TestCase
{
    public function test_backticks()
    {
        $bt = new Backticks();

        $this->assertInstanceOf(Backticks::class, $bt);
    }
}
