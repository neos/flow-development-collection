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
     * The trait name to introduce
     *
     * @var string
     */
    public $traitName;

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
        if (isset($values['traitName'])) {
            $this->traitName = $values['traitName'];
        }
    }
}
