<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
abstract class PrototypeClassG
{
    public $realOrCloned = 'real';


    public function __clone()
    {
        $this->realOrCloned = 'cloned!';
    }
}
