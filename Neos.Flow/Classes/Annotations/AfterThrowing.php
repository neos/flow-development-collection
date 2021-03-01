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

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Declares a method as an after throwing advice to be triggered
 * after any pointcut matching the given expression throws an exception.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class AfterThrowing
{
    /**
     * The pointcut expression. (Can be given as anonymous argument.)
     * @var string
     * @Required
     */
    public $pointcutExpression;

    public function __construct(string $pointcutExpression)
    {
        $this->pointcutExpression = $pointcutExpression;
    }
}
