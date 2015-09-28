<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Reflection\ReflectionService;

/**
 * An annotated class with use statements
 */
class AnnotatedClassWithUseStatements extends AbstractAnnotatedClassWithUseStatements
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var AnnotatedClass
     */
    protected $annotatedClass;

    /**
     * @var Model\SubEntity
     */
    protected $subEntity;
}
