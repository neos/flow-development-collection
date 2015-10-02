<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A simple test context that is registered as a global AOP object
 */
class TestContext
{
    /**
     * @return string
     */
    public function getNameOfTheWeek()
    {
        return 'Robbie';
    }
}
