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

use Neos\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity;

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
