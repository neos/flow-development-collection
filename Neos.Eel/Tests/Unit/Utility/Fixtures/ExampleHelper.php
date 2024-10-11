<?php

namespace Neos\Eel\Tests\Unit\Utility\Fixtures;

class ExampleHelper
{
    public function exampleFunction($argumentOne, $argumentTwo): string
    {
        return json_encode(['ExampleHelper::exampleFunction' => [$argumentOne, $argumentTwo]]);
    }
}
