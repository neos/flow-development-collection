<?php
namespace Neos\Flow\Configuration\ConfigurationSource;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\ApplicationContext;

class ClosureConfigurationSource implements ConfigurationSourceInterface
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Closure
     */
    private $closure;

    public function __construct(string $name, \Closure $closure)
    {
        $this->name = $name;
        $this->closure = $closure;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function process(array $packages, ApplicationContext $context): array
    {
        return $this->closure->__invoke($packages, $context);
    }
}
