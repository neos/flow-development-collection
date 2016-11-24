<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A simple Doctrine ORM 2.5 embeddable for persistence tests
 *
 * @ORM\Embeddable
 */
class TestEmbeddable
{
    /**
     * @var string
     * TODO: Making this nullable is just a workaround for when the parent class is proxied and cloned,
     *       which will currently lead to the embeddable being null.
     * @ORM\Column(nullable=true)
     */
    protected $value;

    /**
     * @param string $value The string value of this value object
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
