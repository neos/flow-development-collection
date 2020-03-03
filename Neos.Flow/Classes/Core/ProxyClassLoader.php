<?php
namespace Neos\Flow\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Flow\Utility\Environment;

/**
 * Class loader for Flow proxy classes.
 * This will be asked before the composer loader.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ProxyClassLoader
{
    /**
     * @var array
     */
    protected $ignoredClassNames = [
        'integer' => true,
        'string' => true,
        'param' => true,
        'return' => true,
        'var' => true,
        'throws' => true,
        'api' => true,
        'todo' => true,
        'fixme' => true,
        'see' => true,
        'license' => true,
        'author' => true,
        'test' => true,
        'deprecated' => true,
        'internal' => true,
        'since' => true,
    ];

    /**
     * @var PhpFrontend
     */
    protected $classesCache;

    /**
     * @var array
     */
    protected $availableProxyClasses;

    /**
     * @param ApplicationContext $context
     */
    public function __construct(ApplicationContext $context)
    {
        $this->initializeAvailableProxyClasses($context);
    }

    /**
     * Injects the cache for storing the renamed original classes
     *
     * @param PhpFrontend $classesCache
     * @return void
     */
    public function injectClassesCache(PhpFrontend $classesCache)
    {
        $this->classesCache = $classesCache;
    }

    /**
     * Loads php files containing classes or interfaces found in the classes directory of
     * a package and specifically registered classes.
     *
     * @param string $className Name of the class/interface to load
     * @return boolean
     */
    public function loadClass($className)
    {
        $className = ltrim($className, '\\');

        $namespaceParts = explode('\\', $className);
        // Workaround for Doctrine's annotation parser which does a class_exists() for annotations like "@param" and so on:
        if (isset($this->ignoredClassNames[$className]) || isset($this->ignoredClassNames[end($namespaceParts)])) {
            return false;
        }

        // Loads any known proxied class:
        if ($this->classesCache !== null && ($this->availableProxyClasses === null || isset($this->availableProxyClasses[implode('_', $namespaceParts)])) && $this->classesCache->requireOnce(implode('_', $namespaceParts)) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Initialize available proxy classes from the cached list.
     *
     * @param ApplicationContext $context
     * @return void
     */
    public function initializeAvailableProxyClasses(ApplicationContext $context = null)
    {
        if ($context === null) {
            return;
        }

        $temporaryDirectoryPath = Environment::composeTemporaryDirectoryName(FLOW_PATH_TEMPORARY_BASE, $context);

        $proxyClasses = @include($temporaryDirectoryPath . 'AvailableProxyClasses.php');
        if ($proxyClasses !== false) {
            $this->availableProxyClasses = $proxyClasses;
        }
    }
}
