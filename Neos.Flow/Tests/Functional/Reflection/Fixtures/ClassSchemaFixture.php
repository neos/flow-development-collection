<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures;

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
 * A fixture for testing class schema building
 *
 * @Flow\Entity
 */
class ClassSchemaFixture
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $things = [];

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Some text with a @param annotation, which should not be parsed.
     *
     * @param string $name
     * @return void
     * @Flow\Validate("$name", type="foo1")
     * @Flow\Validate("$name", type="foo2")
     * @Flow\SkipCsrfProtection
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
