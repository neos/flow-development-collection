<?php
namespace Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * A test value object which is used to test Fluid forms in combination with
 * property mapping
 *
 * @Flow\ValueObject(embedded=true)
 */
class Location
{
    /**
     * @var string
     */
    protected $city;

    /**
     * @param string $city
     */
    public function __construct($city = '')
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }
}
