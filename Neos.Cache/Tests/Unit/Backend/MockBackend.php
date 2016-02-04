<?php
namespace TYPO3\Flow\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\Tests\BaseTestCase;

/**
 * A caching backend which forgets everything immediately
 *
 * Used in \TYPO3\Flow\Cache\FactoryTest
 *
 */
class MockBackend extends BaseTestCase
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
