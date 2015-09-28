<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures\Flow175;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

class OuterPrototype
{
    /**
     * @var GreeterInterface
     */
    private $inner;

    public function __construct(GreeterInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @return Greeter
     */
    public function getInner()
    {
        return $this->inner;
    }
}
