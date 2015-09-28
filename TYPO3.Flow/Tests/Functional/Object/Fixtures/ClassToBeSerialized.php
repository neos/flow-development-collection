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
 * A class to serialize and check if all dependencies are reinjected on unserialize.
 */
class ClassToBeSerialized
{
    /**
     * @var string
     */
    public $someProperty = 'I am not a coffee machine.';

    /**
     * @var string
     */
    protected $protectedProperty = 'I am protected.';

    /**
     * @var string
     */
    private $privateProperty = 'Saving Private Ryan.';

    /**
     * @var string
     */
    protected static $staticProperty = 'I am static';

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassAishInterface
     */
    public $interfaceDeclaredSingletonButImplementationIsPrototype;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB
     */
    public $lazyB;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var SingletonClassC
     */
    public $eagerC;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassB
     */
    public $prototypeB;
}
