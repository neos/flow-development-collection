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
 * Used to set the scope of an object.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Scope
{
    /**
     * The scope of an object: prototype, singleton, session. (Usually given as anonymous argument.)
     * @var string
     */
    public $value = 'prototype';

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->value = $values['value'];
        }
    }
}
