<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * A controller fixture
 *
 * @Flow\Scope("singleton")
 */
class RoutingTestAController extends ActionController
{
    /**
     * @param string $bar
     * @param string $baz
     * @return string
     */
    public function barAndBazAction($bar, $baz)
    {
        return $bar . ' and ' . $baz;
    }
}
