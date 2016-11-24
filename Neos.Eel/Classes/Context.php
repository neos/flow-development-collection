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
use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\ObjectAccess;

/**
 * A Eel evaluation context
 *
 * It works as a variable container with wrapping of return values
 * for safe access without warnings (on missing properties).
 *
 * @Flow\Proxy(false)
 */
class Context
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * Get a value of the context
     *
     * This basically acts as a safe access to non-existing properties, unified array and
     * property access (using getters) and access to the current value (empty path).
     *
     * If a property or key did not exist this method will return NULL.
     *
     * @param string|integer|Context $path The path as string or Context value, will be unwrapped for convenience
     * @return mixed The value
     * @throws EvaluationException
     */
    public function get($path)
    {
        if ($path instanceof Context) {
            $path = $path->unwrap();
        }
        if ($path === null) {
            return null;
        } elseif (is_string($path) || is_integer($path)) {
            if (is_array($this->value)) {
                return array_key_exists($path, $this->value) ? $this->value[$path] : null;
            } elseif (is_object($this->value)) {
                try {
                    return ObjectAccess::getProperty($this->value, $path);
                } catch (PropertyNotAccessibleException $exception) {
                    return null;
                }
            }
        } else {
            throw new EvaluationException('Path is not of type string or integer, got ' . gettype($path), 1344418464);
        }
    }

    /**
     * Get a value by path and wrap it into another context
     *
     * @param string $path
     * @return Context The wrapped value
     */
    public function getAndWrap($path = null)
    {
        return $this->wrap($this->get($path));
    }

    /**
     * Call a method on this context
     *
     * @param string $method
     * @param array $arguments Arguments to the method, if of type Context they will be unwrapped
     * @return mixed
     * @throws \Exception
     */
    public function call($method, array $arguments = [])
    {
        if ($this->value === null) {
            return null;
        } elseif (is_object($this->value)) {
            $callback = [$this->value, $method];
        } elseif (is_array($this->value)) {
            if (!array_key_exists($method, $this->value)) {
                throw new EvaluationException('Array has no function "' . $method . '"', 1344350459);
            }
            $callback = $this->value[$method];
        } else {
            throw new EvaluationException('Needs object or array to call method "' . $method . '", but has ' . gettype($this->value), 1344350454);
        }
        if (!is_callable($callback)) {
            throw new EvaluationException('Method "' . $method . '" is not callable', 1344350374);
        }
        $argumentsCount = count($arguments);
        for ($i = 0; $i < $argumentsCount; $i++) {
            if ($arguments[$i] instanceof Context) {
                $arguments[$i] = $arguments[$i]->unwrap();
            }
        }
        return call_user_func_array($callback, $arguments);
    }

    /**
     * Call a method and wrap the result
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function callAndWrap($method, array $arguments = [])
    {
        return $this->wrap($this->call($method, $arguments));
    }

    /**
     * Wraps the given value in a new Context
     *
     * @param mixed $value
     * @return Context
     */
    public function wrap($value)
    {
        if (!$value instanceof Context) {
            return new static($value);
        } else {
            return $value;
        }
    }

    /**
     * Unwrap the context value recursively
     *
     * @return mixed
     */
    public function unwrap()
    {
        return $this->unwrapValue($this->value);
    }

    /**
     * Unwrap a value by unwrapping nested context objects
     *
     * This method is public for closure access.
     *
     * @param $value
     * @return mixed
     */
    public function unwrapValue($value)
    {
        if (is_array($value)) {
            $self = $this;
            return array_map(function ($item) use ($self) {
                if ($item instanceof Context) {
                    return $item->unwrap();
                } else {
                    return $self->unwrapValue($item);
                }
            }, $value);
        } else {
            return $value;
        }
    }

    /**
     * Push an entry to the context
     *
     * Is used to build array instances inside the evaluator.
     *
     * @param mixed $value
     * @param string $key
     * @return Context
     * @throws EvaluationException
     */
    public function push($value, $key = null)
    {
        if (!is_array($this->value)) {
            throw new EvaluationException('Array operation on non-array context', 1344418485);
        }
        if ($key === null) {
            $this->value[] = $value;
        } else {
            $this->value[$key] = $value;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (is_object($this->value) && !method_exists($this->value, '__toString')) {
            return '[object ' . get_class($this->value) . ']';
        }
        return (string)$this->value;
    }
}
