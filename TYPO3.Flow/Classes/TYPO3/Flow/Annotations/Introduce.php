<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Introduces the given interface or property into any target class matching
 * the given pointcut expression.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
final class Introduce
{
    /**
     * The pointcut expression. (Can be given as anonymous argument.)
     * @var string
     */
    public $pointcutExpression;

    /**
     * The interface name to introduce.
     * @var string
     */
    public $interfaceName;

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (!isset($values['value']) && !isset($values['pointcutExpression'])) {
            throw new \InvalidArgumentException('An Introduce annotation must specify a pointcut expression.', 1318456624);
        }
        $this->pointcutExpression = isset($values['pointcutExpression']) ? $values['pointcutExpression'] : $values['value'];

        if (isset($values['interfaceName'])) {
            $this->interfaceName = $values['interfaceName'];
        }
    }
}
