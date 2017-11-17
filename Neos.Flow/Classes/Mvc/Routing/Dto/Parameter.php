<?php
namespace Neos\Flow\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Parameter
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var array|string|float|int|bool|CacheAwareInterface
     */
    private $value;

    /**
     * @param string $name
     * @param array|bool|float|int|CacheAwareInterface|string $value
     */
    public function __construct(string $name, $value)
    {
        // TODO verify $parameterValue (has to be simple type or instanceof CacheAwareInterface) ?
        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

}
