<?php
namespace TYPO3\Flow\Cache\Backend;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;

/**
 * A caching backend which forgets everything immediately
 *
 * Used in \TYPO3\Flow\Cache\FactoryTest
 *
 */
class MockBackend extends \TYPO3\Flow\Cache\Backend\NullBackend
{
    /**
     * @var mixed
     */
    protected $someOption;

    /**
     * Sets some option
     *
     * @param mixed $value
     * @return void
     */
    public function setSomeOption($value)
    {
        $this->someOption = $value;
    }

    /**
     * Returns the option value
     *
     * @return mixed
     */
    public function getSomeOption()
    {
        return $this->someOption;
    }
}
