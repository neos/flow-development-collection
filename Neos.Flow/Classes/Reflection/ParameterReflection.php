<?php
namespace Neos\Flow\Reflection;

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
     * @return ClassReflection The declaring class
     */
    public function getDeclaringClass()
    {
        return new ClassReflection(parent::getDeclaringClass()->getName());
    }

    /**
     * Returns the parameter class
     *
     * @return ClassReflection|null The parameter class
     */
    public function getClass()
    {
        try {
            $class = parent::getType();
        } catch (\Exception $exception) {
            return null;
        }

        return is_object($class) && !$class->isBuiltin() ? new ClassReflection($class->getName()) : null;
    }

    /**
     * @return string|null The name of a builtin type (e.g. string, int) if it was declared for the parameter (scalar type declaration), null otherwise
     */
    public function getBuiltinType()
    {
        $type = $this->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }
        return $type->isBuiltin() ? $type->getName() : null;
    }
}
