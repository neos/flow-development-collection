<?php
namespace Neos\Flow\Security\Authorization\Privilege\Parameter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Contract for a privilege parameter
 */
interface PrivilegeParameterInterface
{
    /**
     * Note: We can't define constructors in interfaces, but this is assumed to exist in the concrete implementation!
     *
     * @param string $name
     * @param mixed $value
     */
    // public function __construct($name, $value);

    /**
     * Name of this parameter
     *
     * @return string
     */
    public function getName();

    /**
     * The value of this parameter
     *
     * @return mixed
     */
    public function getValue();

    /**
     * @return array
     */
    public function getPossibleValues();

    /**
     * @param mixed $value
     * @return boolean
     */
    public function validate($value);

    /**
     * @return string
     */
    public function getType();

    /**
     * Returns the string representation of this parameter
     * @return string
     */
    public function __toString();
}
