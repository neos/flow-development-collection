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
