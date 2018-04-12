<?php
namespace Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller;

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
use Neos\Flow\Mvc\Controller\ActionController;

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
