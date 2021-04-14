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
     * HTTP Methods. Default to GET
     *
     * @var array
     */
    public $httpMethods = ['GET'];

    /**
	 * URI Pattern. (Can be given as anonymous argument.)
     *
     * Example: 'path/to/action/{actionArgument}'
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
    public $format = 'html';

    /**
     * Append Exceeding Arguments
     *
     * @var bool
     */
    public $appendExceedingArguments = false;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (!isset($values['value']) && !isset($values['uriPattern'])) {
            throw new \InvalidArgumentException('uriPattern is not provided.', 1615113040);
        }

        $this->uriPattern = $values['uriPattern'] ?? $values['value'];
        $this->name = $values['name'] ?? null;

        if (isset($values['httpMethods'])) {
			$this->httpMethods = $values['httpMethods'];
		}
        if (isset($values['format'])) {
			$this->format = $values['format'];
		}
        if (isset($values['appendExceedingArguments'])) {
			$this->appendExceedingArguments = $values['appendExceedingArguments'];
		}
    }
}
