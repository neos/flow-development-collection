<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A controller fixture
 *
 * @Flow\Scope("singleton")
 */
class RoutingTestAController extends \TYPO3\Flow\Mvc\Controller\ActionController
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
