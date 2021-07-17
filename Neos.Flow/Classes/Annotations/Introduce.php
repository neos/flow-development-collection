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
 * Introduces the given interface or property into any target class matching
 * the given pointcut expression.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "PROPERTY"})
 */
#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_PROPERTY|\Attribute::IS_REPEATABLE)]
final class Introduce
{
    /**
     * The pointcut expression. (Can be given as anonymous argument.)
     * @var string
     * @Required
     */
    public $pointcutExpression;

    /**
     * The interface name to introduce.
     * @var string|null
     */
    public $interfaceName;

    /**
     * The trait name to introduce
     *
     * @var string|null
     */
    public $traitName;

    public function __construct(string $pointcutExpression, ?string $interfaceName = null, ?string $traitName = null)
    {
        $this->pointcutExpression = $pointcutExpression;
        $this->interfaceName = $interfaceName;
        $this->traitName = $traitName;
    }
}
