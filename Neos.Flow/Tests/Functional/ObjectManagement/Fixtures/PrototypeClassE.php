<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassE
{
    /**
     * @var string
     */
    protected $nullValue;

    /**
     * @param string $nullValue
     */
    public function __construct($nullValue)
    {
        $this->nullValue = $nullValue;
    }

    /**
     * @return string
     */
    public function getNullValue()
    {
        return $this->nullValue;
    }
}
