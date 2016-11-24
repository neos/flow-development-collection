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

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Utility\Environment;

/**
 * An abstract caching backend
 *
 * @api
 * @deprecated This is replaced by \Neos\Cache\Backend\AbstractBackend which has a different constructor, you need to adapt any custom cache backend to that.
 * @see \Neos\Cache\Backend\AbstractBackend
 */
abstract class AbstractBackend extends \Neos\Cache\Backend\AbstractBackend implements BackendInterface, FlowSpecificBackendInterface
{
    /**
     * The current application context
     *
*@var \Neos\Flow\Core\ApplicationContext
     */
    protected $context;


    /**
     * @var Environment
     */
    protected $environment;

    /**
     * Constructs this backend
     *
     * @param ApplicationContext $context Flow's application context
     * @param array $options Configuration options - depends on the actual backend
     * @param EnvironmentConfiguration $environmentConfiguration
     * @deprecated Use AbstractBackend instead
     * @api
     */
    public function __construct(ApplicationContext $context, array $options = [], EnvironmentConfiguration $environmentConfiguration = null)
    {
        parent::__construct($environmentConfiguration, $options);
        $this->context = $context;
    }

    /**
     * Injects the Environment object
     *
     * @param Environment $environment
     * @return void
     */
    public function injectEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }
}
