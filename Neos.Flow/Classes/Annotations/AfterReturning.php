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
 * Declares a method as an after returning advice to be triggered
 * after any pointcut matching the given expression returns.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class AfterReturning
{
    /**
     * The pointcut expression. (Can be given as anonymous argument.)
     * @var string
     */
    public $pointcutExpression;

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (!isset($values['value']) && !isset($values['pointcutExpression'])) {
            throw new \InvalidArgumentException('An AfterReturning annotation must specify a pointcut expression.', 1318456618);
        }
        $this->pointcutExpression = isset($values['pointcutExpression']) ? $values['pointcutExpression'] : $values['value'];
    }
}
