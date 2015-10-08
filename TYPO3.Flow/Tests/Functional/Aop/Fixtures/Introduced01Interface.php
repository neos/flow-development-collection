<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An interface which is introduced into TargetClass03
 */
interface Introduced01Interface
{
    /**
     * @return string
     */
    public function introducedMethod01();

    /**
     * @param string $someString
     * @return string
     */
    public function introducedMethodWithArguments($someString = 'some string');
}
