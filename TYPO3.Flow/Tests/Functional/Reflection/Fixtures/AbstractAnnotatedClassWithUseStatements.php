<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity;

/**
 * An abstract annotated class with use statements
 */
abstract class AbstractAnnotatedClassWithUseStatements
{
    /**
     * @var Model\SubSubEntity
     */
    protected $subSubEntity;

    /**
     * @var SuperEntity
     */
    protected $superEntity;
}
