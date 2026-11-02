<?php

namespace Neos\Eel\Tests\Functional\FlowQuery\Fixtures;

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
class ExampleNonFinalOperation extends AbstractOperation
{
    protected static $shortName = 'exampleNonFinalOperation';
    protected static $final = false;

    public function evaluate(FlowQuery $query, array $arguments)
    {
    }
}
