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
 * Used to map the request body to a single action argument.
 *
 * Normally, Flow will map the request body into the arguments as an associative array. With this it is possible to
 * map the full body into a single argument without wrapping the request body.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class MapRequestBody
{
    /**
     * Name of the argument to map the request body into. (Can be given as anonymous argument.)
     * @var string
     */
    public $argumentName;

    /**
     * @param array $values
     * @throws \InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (isset($values['value']) || isset($values['argumentName'])) {
            $this->argumentName = ltrim($values['argumentName'] ?? $values['value'], '$');
        }
    }
}
