<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

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
 * An aspect for testing the basic functionality of the AOP framework
 *
 * @Flow\Aspect
 */
class PropertyIntroductionTestingAspect
{
    /**
     * @Flow\Introduce("class(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass04)")
     * @var string
     */
    protected $introducedProtectedProperty;

    /**
     * @Flow\Introduce("class(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass04)")
     * @var array
     */
    public $introducedPublicProperty;

    /**
     * @Flow\Introduce("class(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClass04)")
     * @Flow\Transient
     * @var string
     */
    protected $introducedTransientProperty;
}
