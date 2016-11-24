<?php
namespace Neos\Flow\Tests\Functional\Property\Fixtures;

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
 * A simple entity for PropertyMapper test
 *
 * @Flow\Entity
 */
class TestEntitySubclassWithNewField extends TestEntity
{
    /**
     * @var string
     */
    protected $testField;

    /**
     * @param string $testField
     */
    public function setTestField($testField)
    {
        $this->testField = $testField;
    }

    /**
     * @return string
     */
    public function getTestField()
    {
        return $this->testField;
    }
}
