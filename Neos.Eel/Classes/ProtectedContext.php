<?php
namespace Neos\Eel;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A protected evaluation context
 *
 * - Access to public properties and array is allowed
 * - Methods have to be allowed
 *
 * @Flow\Proxy(false)
 */
class ProtectedContext extends Context
{
    /**
     * @var array
     */
    protected $allowedMethods = [];

    /**
     * Call a method if it is allowed
     *
     * @param string $method
     * @param array $arguments
     * @return mixed|void
     * @throws NotAllowedException
     */
    public function call($method, array $arguments = [])
    {
        if ($this->value === null || isset($this->allowedMethods[$method]) || isset($this->allowedMethods['*']) || ($this->value instanceof ProtectedContextAwareInterface && $this->value->allowsCallOfMethod($method))) {
            return parent::call($method, $arguments);
        }
        throw new NotAllowedException('Method "' . $method . '" is not callable in untrusted context', 1369043080);
    }

    /**
     * Get a value by path and wrap it into another context
     *
     * The list of allowed methods for the given path is applied to the new context.
     *
     * @param string $path
     * @return Context The wrapped value
     */
    public function getAndWrap($path = null)
    {
        // There are some cases where the $path is a ProtectedContext, especially when doing s.th. like
        // foo()[myOffset]. In this case we need to unwrap it.
        if ($path instanceof ProtectedContext) {
            $path = $path->unwrap();
        }

        $context = parent::getAndWrap($path);
        if ($context instanceof ProtectedContext && isset($this->allowedMethods[$path]) && is_array($this->allowedMethods[$path])) {
            $context->allowedMethods = $this->allowedMethods[$path];
        }
        return $context;
    }


    /**
     * Allow the given method (or array of methods) for calls
     *
     * Method can be allowed on the root level of the context or
     * for arbitrary paths. A special method "*" will allow all methods
     * to be called.
     *
     * Examples:
     *
     *   $context->allow('myMethod');
     *
     *   $context->allow('*');
     *
     *   $context->allow(array('String.*', 'Array.reverse'));
     *
     * @param array|string $pathOrMethods
     * @return void
     * @deprecated Use allow() instead. See https://github.com/neos/flow-development-collection/pull/2024
     */
    public function whitelist($pathOrMethods)
    {
        return $this->allow($pathOrMethods);
    }

    /**
     * Allow the given method (or array of methods) for calls
     *
     * Method can be allowed on the root level of the context or
     * for arbitrary paths. A special method "*" will allow all methods
     * to be called.
     *
     * Examples:
     *
     *   $context->allow('myMethod');
     *
     *   $context->allow('*');
     *
     *   $context->allow(array('String.*', 'Array.reverse'));
     *
     * @param array|string $pathOrMethods
     * @return void
     */
    public function allow($pathOrMethods)
    {
        if (!is_array($pathOrMethods)) {
            $pathOrMethods = [$pathOrMethods];
        }
        foreach ($pathOrMethods as $pathOrMethod) {
            $parts = explode('.', $pathOrMethod);
            $current = &$this->allowedMethods;
            $count = count($parts);
            for ($i = 0; $i < $count; $i++) {
                if ($i === $count - 1) {
                    $current[$parts[$i]] = true;
                } else {
                    $current[$parts[$i]] = [];
                    $current = &$current[$parts[$i]];
                }
            }
        }
    }
}
