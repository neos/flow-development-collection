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
 * Declares a named pointcut. The annotated method does not become an advice
 * but can be used as a named pointcut instead of the given expression.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Pointcut
{
    /**
     * The pointcut expression. (Can be given as anonymous argument.)
     * @var string
     * @Required
     */
    public $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }
}
