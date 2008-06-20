<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Configuration for the FLOW3 Framework                                  *
 *                                                                        *
 * This file contains the default base configuration for the FLOW3        *
 * Framework. Don't modify this file but add configuration options to     *
 * the FLOW3.php file in the in global Configuration/ directory instead.  *
 *                                                                        */

/**
 * @package FLOW3
 * @version $Id$
 */

/**
 * Defines the global, last-resort exception handler.
 *
 * @var F3_FLOW3_Error_DevelopmentExceptionHandlerInterface
 */
$c->exceptionHandler->className = 'F3_FLOW3_Error_DebugExceptionHandler';

/**
 * Defines which errors should result in an exception thrown - all other error
 * levels will be silently ignored.
 *
 * @var array
 */
$c->errorHandler->exceptionalErrors = array(E_ERROR, E_RECOVERABLE_ERROR);

/**
 * Enable or disable caching of the component configurations. If caching is
 * enabled, a cache backend must be properly configured.
 *
 * @var boolean
 */
$c->component->configurationCache->enable = TRUE;

/**
 * Define the backend used for caching component configurations. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @var F3_FLOW3_Cache_BackendInterface
 */
$c->component->configurationCache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * Options which are passed the backend of the component configuration cache.
 *
 * @var array
 */
$c->component->configurationCache->backendOptions = array();


/**
 * Defines the available request handlers. Each of them must implement the
 * F3_FLOW3_MVC_RequestHandlerInterface.
 *
 * @var array
 */
$c->mvc->availableRequestHandlers = array('F3_FLOW3_MVC_Web_RequestHandler', 'F3_FLOW3_MVC_CLI_RequestHandler');

/**
 * Enable or disable the caching of proxy classes which were built by
 * the AOP Framework.
 */
$c->aop->proxyCache->enable = TRUE;

/**
 * Define the backend used for caching AOP proxy classes. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @var F3_FLOW3_Cache_BackendInterface
 */
$c->aop->proxyCache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * Options which are passed the backend of the AOP proxy cache.
 *
 * @var array
 */
$c->aop->proxyCache->backendOptions = array();


/**
 * Enable or disable the caching of reflection information which is provided
 * by the reflection service.
 */
$c->reflection->cache->enable = TRUE;

/**
 * Defines the backend used for caching reflection information. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @var F3_FLOW3_Cache_BackendInterface
 */
$c->reflection->cache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * Options which are passed the backend of the reflection cache
 *
 * @var array
 */
$c->reflection->cache->backendOptions = array();

/**
 * Define the backend used for caching resource metadata. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @var F3_FLOW3_Cache_BackendInterface
 */
$c->resource->cache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * Options which are passed the backend of the resource metadata cache.
 *
 * @var array
 */
$c->resource->cache->backendOptions = array();

/**
 * The path for the public resources mirror
 *
 * @var string
 */
$c->resource->cache->publicPath = FLOW3_PATH_PUBLIC . 'Resources/';

/**
 * The strategy to use when caching files for public resources. Specify one of
 * F3_FLOW3_Resource_Manager::CACHE_STRATEGY_PACKAGE or
 * F3_FLOW3_Resource_Manager::CACHE_STRATEGY_FILE
 *
 * @var string
 */
$c->resource->cache->strategy = F3_FLOW3_Resource_Manager::CACHE_STRATEGY_PACKAGE;

/**
 * Define the backend used for persistence. Specify the name of a component
 * implementing the F3_FLOW3_Persistence_BackendInterface.
 *
 * @var F3_FLOW3_Persistence_BackendInterface
 */
$c->persistence->backend = '';

?>