<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

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
