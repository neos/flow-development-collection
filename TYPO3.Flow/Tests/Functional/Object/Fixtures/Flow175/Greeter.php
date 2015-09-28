<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures\Flow175;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("prototype")
 */
class Greeter implements GreeterInterface
{
    public function greet($who)
    {
        return "Hello $who!";
    }
}
