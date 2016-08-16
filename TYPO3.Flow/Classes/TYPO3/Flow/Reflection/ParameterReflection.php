<?php
namespace TYPO3\Flow\Reflection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Extended version of the ReflectionParameter
 *
 * @Flow\Proxy(false)
 */
class ParameterReflection extends \ReflectionParameter
{
    /**
     * @var string
     */
    protected $parameterClassName;

    /**
     * Returns the declaring class
     *
     * @return \TYPO3\Flow\Reflection\ClassReflection The declaring class
     */
    public function getDeclaringClass()
    {
        return new ClassReflection(parent::getDeclaringClass()->getName());
    }

    /**
     * Returns the parameter class
     *
     * @return \TYPO3\Flow\Reflection\ClassReflection The parameter class
     */
    public function getClass()
    {
        try {
            $class = parent::getClass();
        } catch (\Exception $exception) {
            return null;
        }

        return is_object($class) ? new ClassReflection($class->getName()) : null;
    }

    /**
     * @return string The name of a builtin type (e.g. string, int) if it was declared for the parameter (scalar type declaration), null otherwise
     */
    public function getBuiltinType()
    {
        if (!is_callable(array($this, 'getType'))) {
            return null;
        }
        $type = $this->getType();
        if ($type === null || !$type->isBuiltin()) {
            return null;
        }
        return (string)$type;
    }
}
