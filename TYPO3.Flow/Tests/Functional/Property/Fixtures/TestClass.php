<?php
namespace TYPO3\Flow\Tests\Functional\Property\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;

/**
 * A simple class for PropertyMapper test
 *
 */
class TestClass
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $size;

    /**
     * @var boolean
     */
    protected $signedCla;

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
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param integer $size
     * @return void
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return boolean
     */
    public function getSignedCla()
    {
        return $this->signedCla;
    }

    /**
     * @param boolean $signedCla
     * @return void
     */
    public function setSignedCla($signedCla)
    {
        $this->signedCla = $signedCla;
    }
}
