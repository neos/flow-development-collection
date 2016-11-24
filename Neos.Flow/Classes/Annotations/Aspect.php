<?php
namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Marks a class as an aspect.
 *
 * The class will be read by the AOP framework of Flow and inspected for
 * pointcut expressions and advice.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Aspect
{
}
