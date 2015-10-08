<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Class CommonObject
 * Representation of an object handled as "\Doctrine\DBAL\Types\Type::OBJECT"
 *
 * @package TYPO3\Flow\Tests\Functional\Persistence\Fixtures
 */
class CommonObject
{
    /**
     * @var string
     */
    protected $foo;

    /**
     * @param string $foo
     * @return $this
     */
    public function setFoo($foo = null)
    {
        $this->foo = $foo;
        return $this;
    }

    /**
     * @return string
     */
    public function getFoo()
    {
        return $this->foo;
    }
}
