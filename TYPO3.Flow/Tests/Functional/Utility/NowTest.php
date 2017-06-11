<?php
namespace TYPO3\Flow\Tests\Functional\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Utility;

/**
 * Functional test for the Now class
 */
class NowTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function nowReturnsAUniqueTimestamp()
    {
        $now = $this->objectManager->get(Utility\Now::class);
        $alsoNow = $this->objectManager->get(Utility\Now::class);
        $this->assertSame($now->getTimeStamp(), $alsoNow->getTimeStamp());
    }
}
