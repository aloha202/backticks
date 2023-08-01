<?php

namespace App\Libraries\QueryParser\Evaluator\Tag;

use App\Libraries\QueryParser\Exception\InvalidTagNameException;
use Tests\TestCase;
class TagNameTest extends TestCase
{
    /**
     * @return void
     * @dataProvider dataException
     */
    public function testException($input) {
        $this->expectException(InvalidTagNameException::class);
        new Name($input);
    }

    public static function dataException(): array
    {
        return [
            [''],
            [' '],
            ['invalid-name'],
            ['inv@lid::name,,'],
        ];
    }
}
