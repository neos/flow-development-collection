<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class UriArgumentObjectWithToString
{
    protected $identifier = 'String To Identify Object';

    public function __toString(): string
    {
        return $this->identifier;
    }
}
