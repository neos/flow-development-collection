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
 * Declares a named pointcut. The annotated method does not become an advice
 * but can be used as a named pointcut instead of the given expression.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Pointcut
{
    /**
     * The pointcut expression. (Can be given as anonymous argument.)
     * @var string
     */
    public $expression;

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (!isset($values['value']) && !isset($values['expression'])) {
            throw new \InvalidArgumentException('A Pointcut annotation must specify a pointcut expression.', 1318456604);
        }
        $this->expression = isset($values['expression']) ? $values['expression'] : $values['value'];
    }
}
