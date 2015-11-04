<?php
namespace TYPO3\Eel\Tests\Functional\FlowQuery\Fixtures;

/*
 * This file is part of the TYPO3.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

class ExampleNonFinalOperation extends \TYPO3\Eel\FlowQuery\Operations\AbstractOperation
{
    protected static $shortName = 'exampleNonFinalOperation';
    protected static $final = false;

    public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $query, array $arguments)
    {
    }
}
