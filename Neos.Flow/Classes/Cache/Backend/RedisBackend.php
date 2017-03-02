<?php
namespace Neos\Flow\Cache\Backend;

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

/**
 * @deprecated Use \Neos\Cache\Backend\RedisBackend instead
 */
class RedisBackend extends \Neos\Cache\Backend\RedisBackend implements FlowSpecificBackendInterface
{
    use BackendCompatibilityTrait;

    /**
     * Constructs this backend
     *
     * @param ApplicationContext $context Flow's application context
     * @param array $options Configuration options - depends on the actual backend
     * @param \Redis $redis
     */
    public function __construct(ApplicationContext $context, array $options = [], \Redis $redis = null)
    {
        $this->context = $context;
        if ($redis !== null) {
            $options['redis'] = $redis;
        }
        $environmentConfiguration = $this->createEnvironmentConfiguration($context);
        parent::__construct($environmentConfiguration, $options);
    }
}
