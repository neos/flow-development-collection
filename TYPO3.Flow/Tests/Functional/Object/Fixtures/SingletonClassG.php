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
 * A class that is declared singleton from Objects.yaml with constructor injection
 */
class SingletonClassG
{
    /**
     * @var PrototypeClassAishInterface
     */
    public $prototypeA;

    /**
     * @param PrototypeClassAishInterface $prototypeA
     */
    public function __construct(PrototypeClassAishInterface $prototypeA)
    {
        $this->prototypeA = $prototypeA;
    }
}
