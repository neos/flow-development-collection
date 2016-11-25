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
 * Controls how a property or method argument will be validated by Flow.
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
final class Validate
{
    /**
     * The validator type, either a FQCN or a Flow validator class name.
     * @var string
     */
    public $type;

    /**
     * Options for the validator, validator-specific.
     * @var array
     */
    public $options = array();

    /**
     * The name of the argument this annotation is attached to, if used on a method. (Can be given as anonymous argument.)
     * @var string
     */
    public $argumentName;

    /**
     * The validation groups for which this validator should be executed.
     * @var array
     */
    public $validationGroups = array('Default');

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (!isset($values['type'])) {
            throw new \InvalidArgumentException('Validate annotations must be given a validator type.', 1318494791);
        }
        $this->type = $values['type'];

        if (isset($values['options']) && is_array($values['options'])) {
            $this->options = $values['options'];
        }

        if (isset($values['value']) || isset($values['argumentName'])) {
            $this->argumentName = ltrim(isset($values['argumentName']) ? $values['argumentName'] : $values['value'], '$');
        }

        if (isset($values['validationGroups']) && is_array($values['validationGroups'])) {
            $this->validationGroups = $values['validationGroups'];
        }
    }
}
