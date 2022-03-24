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


class ExampleNonFinalOperation extends \Neos\Eel\FlowQuery\Operations\AbstractOperation
{
    protected static $shortName = 'exampleNonFinalOperation';
    protected static $final = false;

    public function evaluate(\Neos\Eel\FlowQuery\FlowQuery $query, array $arguments)
    {
    }
}
