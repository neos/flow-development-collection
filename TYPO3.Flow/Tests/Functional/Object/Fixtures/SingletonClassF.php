<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class of scope singleton
 *
 * @Flow\Scope("singleton")
 */
class SingletonClassF
{
    /**
     * @var string
     */
    protected $nullValue;

    /**
     * @param string $nullValue
     */
    public function __construct($nullValue)
    {
        $this->nullValue = $nullValue;
    }

    /**
     * @return string
     */
    public function getNullValue()
    {
        return $this->nullValue;
    }
}
