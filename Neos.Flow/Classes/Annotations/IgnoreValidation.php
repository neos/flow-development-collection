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
 * Used to ignore validation on a specific method argument or class property.
 *
 * By default no validation will be executed for the given argument. To gather validation results for further
 * processing, the "evaluate" option can be set to true (while still ignoring any validation error).
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD", "PROPERTY"})
 */
#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::TARGET_PROPERTY|\Attribute::IS_REPEATABLE)]
final class IgnoreValidation
{
    /**
     * Name of the argument to skip validation for. (Can be given as anonymous argument.)
     * @var string|null
     */
    public $argumentName;

    /**
     * Whether to evaluate the validation results of the argument
     * @var boolean
     */
    public $evaluate = false;

    public function __construct(string $argumentName = null, bool $evaluate = false)
    {
        $this->argumentName = $argumentName ? ltrim($argumentName, '$') : null;
        $this->evaluate = $evaluate;
    }
}
