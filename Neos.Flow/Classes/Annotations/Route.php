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
 * Used to configure Routes from a method
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Route
{
    /**
     * Name
     *
     * @var string|null
     */
    public $name;

    /**
     * HTTP Methods
     *
     * @var array|null
     */
    public $httpMethods;

    /**
     * URI Pattern
     *
     * Example: GRANT
     *
     * @var string
     */
    public $uriPattern;

    /**
     * Format
     *
     * Example: html
     *
     * @var string|null
     */
    public $format;

    /**
     * Append Exceeding Arguments
     *
     * @var bool|null
     */
    public $appendExceedingArguments;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (!isset($values['uriPattern'])) {
            throw new \InvalidArgumentException('uriPattern is not provided.', 1615113040);
        }

        $this->uriPattern = $values['uriPattern'];
        $this->name = $values['name'] ?? null;
        $this->httpMethods = $values['httpMethods'] ?? null;
        $this->format = $values['format'] ?? null;
        $this->appendExceedingArguments = $values['appendExceedingArguments'] ?? null;
    }
}
