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
 * Used to ignore validation on a specific method argument or class property.
 *
 * By default no validation will be executed for the given argument. To gather validation results for further
 * processing, the "evaluate" option can be set to true (while still ignoring any validation error).
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
final class IgnoreValidation
{
    /**
     * Name of the argument to skip validation for. (Can be given as anonymous argument.)
     * @var string
     */
    public $argumentName;

    /**
     * Whether to evaluate the validation results of the argument
     * @var boolean
     */
    public $evaluate = false;

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (isset($values['value']) || isset($values['argumentName'])) {
            $this->argumentName = ltrim(isset($values['argumentName']) ? $values['argumentName'] : $values['value'], '$');
        }

        if (isset($values['evaluate'])) {
            $this->evaluate = (boolean)$values['evaluate'];
        }
    }
}
