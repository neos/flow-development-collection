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
 * @var F3_FLOW3_Error_ExceptionHandlerInterface
 */
$c->exceptionHandler->className = 'F3_FLOW3_Error_ProductionExceptionHandler';

/**
 * Defines which errors should result in an exception thrown - all other error
 * levels will be silently ignored.
 *
 * @var array
 */
$c->errorHandler->exceptionalErrors = array(E_ERROR, E_RECOVERABLE_ERROR);

/**
 * Defines the base directory which FLOW3 may use for storing different kinds
 * of temporary files.
 *
 * The directory must be writable and FLOW3 will automatically create a sub
 * directory "FLOW3" which will contain the actualy temporary files.
 *
 * @var string
 */
$c->utility->environment->temporaryDirectoryBase = FLOW3_PATH_DATA . 'Temporary/';


/**
 * Defines the default cache backend for FLOW3.
 *
 * It is suggested that all other sub packages and packages refer to this
 * configuration by default if they have no special requirements in terms
 * of the caching backend.
 *
 * @var F3_FLOW3_Cache_BackendInterface
 */
$c->cache->defaultCache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * Defines the default configuration options for the cache backend.
 *
 * See also the description of $c->cache->defaultCache->backend
 *
 * @var array
 */
$c->cache->defaultCache->backendOptions = array();

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
$c->component->configurationCache->backend = $c->cache->defaultCache->backend;

/**
 * Options which are passed the backend of the component configuration cache.
 *
 * @var array
 */
$c->component->configurationCache->backendOptions = $c->cache->defaultCache->backendOptions;

/**
 * Defines the available request handlers. Each of them must implement the
 * F3_FLOW3_MVC_RequestHandlerInterface.
 *
 * @var array
 */
$c->mvc->availableRequestHandlers = array('F3_FLOW3_MVC_Web_RequestHandler', 'F3_FLOW3_MVC_CLI_RequestHandler');

/**
 * Enable or disable the whole AOP framework.
 *
 * Note that certain features depend on and might not work anymore if you
 * disable the AOP framework.
 */
$c->aop->enable = TRUE;

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
$c->aop->proxyCache->backend = $c->cache->defaultCache->backend;

/**
 * Options which are passed the backend of the AOP proxy cache.
 *
 * @var array
 */
$c->aop->proxyCache->backendOptions = $c->cache->defaultCache->backendOptions;


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
$c->reflection->cache->backend = $c->cache->defaultCache->backend;

/**
 * Options which are passed the backend of the reflection cache
 *
 * @var array
 */
$c->reflection->cache->backendOptions = $c->cache->defaultCache->backendOptions;

/**
 * Define the backend used for caching resource metadata. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @var F3_FLOW3_Cache_BackendInterface
 */
$c->resource->cache->backend = $c->cache->defaultCache->backend;

/**
 * Options which are passed the backend of the resource metadata cache.
 *
 * @var array
 */
$c->resource->cache->backendOptions = $c->cache->defaultCache->backendOptions;

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
 * Defines the backend which is used for storing the session data.
 * If no session functionality is needed / wanted, just use the "Transient" session.
 *
 * @var F3_FLOW3_Session_Interface
 */
$c->session->backend->className = (PHP_SAPI == 'cli') ? 'F3_FLOW3_Session_Transient' : 'F3_FLOW3_Session_PHP';

/**
 * Whether to enable FLOW3's persistence manager or not.
 *
 * @var boolean
 */
$c->persistence->enable = FALSE;

/**
 * If set to TRUE, the firewall will reject any request that is not
 * explicitly allowed by a configured request filter.
 *
 * @var boolean
 */
$c->security->firewall->rejectAll = FALSE;

/**
 * The filter configuration for the firewall. Here is an example configuration array:
 *
 * $c->security->firewall->filters = array(
 * 		array(
 * 			'patternType' => 'URL',
 * 			'patternValue' => '/some/url/.*',
 * 			'interceptor' => 'AccessGrant'
 * 		),
 * 		array(
 * 			'patternType' => 'URL',
 * 			'patternValue' => '/some/url/blocked.*',
 * 			'interceptor' => 'AccessDeny'
 * 		)
 * );
 *
 * If the objects are in the namespace "F3_FLOW3_Security" it is enough to specify
 * the last name of the classname, e.g. AccessGrant
 *
 * @var array
 */
$c->security->firewall->filters = array();

/**
 * Array of authentication providers that should be used for authentication.
 * If you set a request pattern the provider will only be called if the pattern
 * matches the current request. If the objects are in the namespace
 * "F3_FLOW3_Security" it is enough to specify the last name of the classname,
 * e.g. UsernamePassword
 * Note: Authentication will be performed in the given order of the providers.
 * So make sure, that the primary authentication method is the first array entry.
 *
 * @var array
 */
$c->security->authentication->providers = array(
	array(
		'provider' => 'UsernamePassword',
		'patternType' => '',
		'patternVaue' => ''
	)
);

/**
 * If set to TRUE, authentication will only succeed, if all active tokens (authentication mechanisms)
 * can be authenticated.
 *
 * @var boolean
 */
$c->security->authentication->authenticateAllTokens = FALSE;

?>