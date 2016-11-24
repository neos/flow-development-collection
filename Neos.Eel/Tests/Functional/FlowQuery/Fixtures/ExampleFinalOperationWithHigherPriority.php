<?php
namespace Neos\Eel\Tests\Functional\FlowQuery\Fixtures;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

class ExampleFinalOperationWithHigherPriority extends \Neos\Eel\FlowQuery\Operations\AbstractOperation
{
    protected static $shortName = 'exampleFinalOperation';
    protected static $final = true;

    protected static $priority = 100;

    public function evaluate(\Neos\Eel\FlowQuery\FlowQuery $query, array $arguments)
    {
        return 'Priority 100';
    }
}
