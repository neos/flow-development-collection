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
 * @type F3_FLOW3_Error_DevelopmentExceptionHandlerInterface
 */
$c->exceptionHandler->className = 'F3_FLOW3_Error_ProductionExceptionHandler';

/**
 * Defines which errors should result in an exception thrown - all other error
 * levels will be silently ignored.
 *
 * @type array
 */
$c->errorHandler->exceptionalErrors = array(E_ERROR, E_RECOVERABLE_ERROR);

/**
 * Enable or disable caching of the component configurations. If caching is
 * enabled, a cache backend must be properly configured.
 *
 * @type boolean
 */
$c->component->configurationCache->enable = TRUE;

/**
 * Define the backend used for caching component configurations. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @type F3_FLOW3_Cache_BackendInterface
 */
$c->component->configurationCache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * Options which are passed the cache backend.
 *
 * @type array
 */
$c->component->configurationCache->backendOptions = array();

/**
 * Enable or disable the caching of proxy classes which were built by
 * the AOP Framework.
 */
$c->aop->proxyCache->enable = TRUE;

/**
 * Define the backend used for caching AOP proxy classes. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @type F3_FLOW3_Cache_BackendInterface
 */
$c->aop->proxyCache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * The path for the public resources mirror used by the configuration path
 *
 * @type string
 */
$c->resource->cache->publicPath = FLOW3_PATH_PUBLIC . 'Resources/';

/**
 * The strategy to use when caching files for public resources. Specify one of
 * F3_FLOW3_Resource_Manager::CACHE_STRATEGY_PACKAGE or
 * F3_FLOW3_Resource_Manager::CACHE_STRATEGY_FILE
 *
 * @type string
 */
$c->resource->cache->strategy = F3_FLOW3_Resource_Manager::CACHE_STRATEGY_PACKAGE;
?>