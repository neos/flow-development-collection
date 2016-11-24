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
use Neos\Flow\Utility\Algorithms;

/**
 * A simple entity for persistence tests that is not proxied (no AOP/DI)
 *
 * @Flow\Entity
 * @Flow\Proxy(false)
 * @ORM\Table(name="persistence_unproxiedtestentity")
 */
class UnproxiedTestEntity
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(length=40)
     */
    protected $uuid;

    /**
     * @var string
     * @Flow\Validate(type="StringLength", options={"minimum"=3})
     */
    protected $name = '';

    public function __construct()
    {
        $this->uuid = Algorithms::generateUUID();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
