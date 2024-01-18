<?php

namespace Neos\Eel\Tests\Unit\Utility\Fixtures;

use Neos\Eel\ProtectedContextAwareInterface;

class SecondExampleProtectedContextAwareHelper implements ProtectedContextAwareInterface
{
    public function exampleFunction($argumentOne, $argumentTwo): string
    {
        return json_encode(['SecondExampleProtectedContextAwareHelper::exampleFunction' => [$argumentOne, $argumentTwo]]);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
