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

use Neos\Cache\EnvironmentConfiguration;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Utility\Environment;

/**
 * Helper trait for transitional backends not receiving
 * the new (cache) EnvironmentConfiguration object
 */
trait BackendCompatibilityTrait
{
    /**
     * The current application context
     *
     * @var ApplicationContext
     */
    protected $context;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @param ApplicationContext $context
     * @param string $temporaryDirectory
     * @return EnvironmentConfiguration
     */
    protected function createEnvironmentConfiguration(ApplicationContext $context, $temporaryDirectory = null)
    {
        if ($temporaryDirectory === null) {
            $temporaryDirectory = FLOW_PATH_TEMPORARY;
        }

        return new EnvironmentConfiguration(
            FLOW_PATH_ROOT . '~' . (string)$context,
            $temporaryDirectory,
            PHP_MAXPATHLEN
        );
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
        $this->environmentConfiguration = $this->createEnvironmentConfiguration($environment->getContext(), $environment->getPathToTemporaryDirectory());
    }
}
