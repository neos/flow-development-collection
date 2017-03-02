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
 * A class of scope prototype
 *
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class PrototypeClassG
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var bool
     */
    protected $destructed = false;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isDestructed()
    {
        return $this->destructed;
    }

    /**
     * @param bool $destructed
     */
    public function setDestructed($destructed)
    {
        $this->destructed = $destructed;
    }

    public function shutdownObject()
    {
        $this->setDestructed(true);
    }
}
