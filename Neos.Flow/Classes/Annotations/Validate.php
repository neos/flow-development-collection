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
 * Controls how a property or method argument will be validated by Flow.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD", "PROPERTY"})
 */
#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::TARGET_PROPERTY|\Attribute::IS_REPEATABLE)]
final class Validate
{
    /**
     * The validator type, either a FQCN or a Flow validator class name.
     * @var string|null
     */
    public $type;

    /**
     * Options for the validator, validator-specific.
     * @var array
     */
    public $options = [];

    /**
     * The name of the argument this annotation is attached to, if used on a method. (Can be given as anonymous argument.)
     * @var string
     */
    public $argumentName;

    /**
     * The validation groups for which this validator should be executed.
     * @var array
     */
    public $validationGroups = ['Default'];

    public function __construct(?string $argumentName = null, string $type = null, array $options = [], ?array $validationGroups = null)
    {
        $this->type = $type;

        $this->options = $options;

        if ($argumentName !== null) {
            $this->argumentName = ltrim($argumentName, '$');
        }

        if ($validationGroups !== null) {
            $this->validationGroups = $validationGroups;
        }
    }
}
