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
 * A class which references other dependencies from the same namespace.
 */
class ClassWithNonNamespacedDependencies
{
    /**
     * @Flow\Inject(lazy=FALSE)
     * @var SingletonClassB
     */
    protected $singletonClassB;

    /**
     * @Flow\Inject(lazy=FALSE)
     * @var SubNamespace\AnotherClass
     */
    protected $classFromSubNamespace;

    /**
     * @return SingletonClassB
     */
    public function getSingletonClassB()
    {
        return $this->singletonClassB;
    }

    /**
     * @return SubNamespace\AnotherClass
     */
    public function getClassFromSubNamespace()
    {
        return $this->classFromSubNamespace;
    }
}
