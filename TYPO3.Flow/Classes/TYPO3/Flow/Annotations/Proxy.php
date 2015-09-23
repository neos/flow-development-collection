<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Used to disable proxy building for an object.
 *
 * If disabled, neither Dependency Injection nor AOP can be used
 * on the object.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Proxy
{
    /**
     * Whether proxy building for the target is disabled. (Can be given as anonymous argument.)
     * @var boolean
     */
    public $enabled = true;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['enabled'])) {
            $this->enabled = (boolean)$values['enabled'];
        } elseif (isset($values['value'])) {
            $this->enabled = (boolean)$values['value'];
        }
    }
}
