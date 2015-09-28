<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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
    protected $things = array();

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
