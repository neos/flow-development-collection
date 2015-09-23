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
 * An aspect for testing the basic functionality of the AOP framework
 *
 * @Flow\Aspect
 */
class PropertyIntroductionTestingAspect
{
    /**
     * @Flow\Introduce("class(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass04)")
     * @var string
     */
    protected $introducedProtectedProperty;

    /**
     * @Flow\Introduce("class(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass04)")
     * @var array
     */
    public $introducedPublicProperty;
}
