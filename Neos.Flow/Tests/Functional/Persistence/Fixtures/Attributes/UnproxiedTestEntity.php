<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures\Attributes;

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
 */
#[Flow\Entity]
#[Flow\Proxy(false)]
#[ORM\Table(name: "persistence_attributes_unproxiedtestentity")]
class UnproxiedTestEntity
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    protected string $uuid;

    #[Flow\Validate(type: 'StringLength', options: [['minimum' => 3]])]
    protected string $name = '';

    public function __construct()
    {
        $this->uuid = Algorithms::generateUUID();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
