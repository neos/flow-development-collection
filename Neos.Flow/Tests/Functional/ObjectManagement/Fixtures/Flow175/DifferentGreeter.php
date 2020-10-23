<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\Flow175;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class DifferentGreeter implements GreeterInterface
{
    public function greet($who)
    {
        return 'Hello ' . $who . ' from a different greeter!';
    }
}
