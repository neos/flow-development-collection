<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

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
     * @var boolean
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
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean
     */
    public function isDestructed()
    {
        return $this->destructed;
    }

    /**
     * @param boolean $destructed
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
