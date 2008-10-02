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
 * @var F3::FLOW3::Error::ExceptionHandlerInterface
 */
$c->exceptionHandler->className = 'F3::FLOW3::Error::ProductionExceptionHandler';

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
 * @var F3::FLOW3::Cache::BackendInterface
 */
$c->cache->defaultCache->backend = 'F3::FLOW3::Cache::Backend::File';

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
 * name of a component implementing the F3::FLOW3::Cache::BackendInterface.
 *
 * @var F3::FLOW3::Cache::BackendInterface
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
 * F3::FLOW3::MVC::RequestHandlerInterface.
 *
 * @var array
 */
$c->mvc->availableRequestHandlers = array('F3::FLOW3::MVC::Web::RequestHandler', 'F3::FLOW3::MVC::CLI::RequestHandler');

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
 * name of a component implementing the F3::FLOW3::Cache::BackendInterface.
 *
 * @var F3::FLOW3::Cache::BackendInterface
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
 * name of a component implementing the F3::FLOW3::Cache::BackendInterface.
 *
 * @var F3::FLOW3::Cache::BackendInterface
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
 * name of a component implementing the F3::FLOW3::Cache::BackendInterface.
 *
 * @var F3::FLOW3::Cache::BackendInterface
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
 * F3::FLOW3::Resource::Manager::CACHE_STRATEGY_PACKAGE or
 * F3::FLOW3::Resource::Manager::CACHE_STRATEGY_FILE
 *
 * @var string
 */
$c->resource->cache->strategy = F3::FLOW3::Resource::Manager::CACHE_STRATEGY_PACKAGE;

/**
 * Defines the backend which is used for storing the session data.
 * If no session functionality is needed / wanted, just use the "Transient" session.
 *
 * @var F3::FLOW3::Session::SessionInterface
 */
$c->session->backend->className = (PHP_SAPI == 'cli') ? 'F3::FLOW3::Session::Transient' : 'F3::FLOW3::Session::PHP';

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
 * If the objects are in the namespace "F3::FLOW3::Security" it is enough to specify
 * the last name of the classname, e.g. AccessGrant
 *
 * @var array
 */
$c->security->firewall->filters = array();

/**
 * Array of authentication providers that should be used for authentication.
 * If you set a request pattern the provider will only be called if the pattern
 * matches the current request. If the objects are in the namespace
 * "F3::FLOW3::Security" it is enough to specify the last name of the classname,
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

/**
 * An array of access decision voters that should vote when access decisions are made
 *
 * @var array
 */
$c->security->accessDecisionVoters = array('F3::FLOW3::Security::Authorization::Voter::ACL');

/**
 * If this is set to TRUE access will be granted even if all voters abstain
 *
 * @var boolean
 */
$c->security->allowAccessIfAllVotersAbstain = FALSE;

/**
 * The security policy resources configuration. Here is an example configuration array:
 *
 * $c->security->policy->resources = array(
 * 		'theOneAndOnlyResource' => 'method(F3::TestPackage::BasicClass->setSomeProperty())',
 * 		'theOtherLonelyResource' => 'method(F3::TestPackage::BasicClassValidator->.*())',
 * 		'theIntegrativeResource' => 'theOneAndOnlyResource || theOtherLonelyResource',
 * );
 *
 * @var array
 */
$c->security->policy->resources = array();

/**
 * The security policy roles configuration. Here is an example configuration array:
 *
 * $c->security->policy->roles = array(
 *		'ADMINISTRATOR' => array(),
 * 		'DEVELOPER' => array(),
 * 		'CUSTOMER' => array(),
 * 		//Role that is a child role of CUSTOMER
 * 		'PRIVILEGED_CUSTOMER' => array('CUSTOMER'),
 * );
 *
 * @var array
 */
$c->security->policy->roles = array();

/**
 * The security policy acls configuration connects the resources with the roles by assigning
 * privileges. Here is an example configuration array:
 *
 * $c->security->policy->acls = array(
 * 		'ADMINISTRATOR' => array(
 * 			'deleteMethods' => 'ACCESS_GRANT',
 * 			'MyPackageUpdateMethods' => 'ACCESS_DENY'
 * 		);
 * 		'CUSTOMER' = array(
 * 			'deleteMethods' => 'ACCESS_DENY',
 * 			'MyPackageUpdateMethods' => 'ACCESS_DENY'
 * 		);
 * );
 *
 * @var array
 */
$c->security->policy->acls = array();

/**
 * Define the backend used for caching the security policy ACLs. Specify the
 * name of a component implementing the F3::FLOW3::Cache::BackendInterface.
 *
 * @var F3::FLOW3::Cache::BackendInterface
 */
$c->security->policy->aclCache->backend = $c->cache->defaultCache->backend;

/**
 * Options which are passed the backend of the security policy ACL cache.
 *
 * @var array
 */
$c->security->policy->aclCache->backendOptions = $c->cache->defaultCache->backendOptions;

/**
 * This is a very dirty and not recommended playground option.
 *
 * As long as we don't have a proper mechanism for resolving authentication entry points,
 * this URI may point to some login page. The MVC Dispatcher is the only one who knows
 * about this option (and nobody else should use it because we'll discard it soon without
 * further notice).
 *
 * Currently used by the TYPO3 package for demo purposes.
 *
 * @var string
 */
$c->security->loginPageURIForDemoPurposes = '';

/**
 * The default locale identifier.
 *
 * @var string
 */
$c->locale->defaultLocaleIdentifier = 'en_Latn_EN';

/**
 * The default locale.
 *
 * This configuration option is automatically filled by FLOW3's locale
 * framework with a locale object considered to be the default locale
 * of the application.
 *
 * The locale object will usually reflect the setting made in
 * $c->locale->defaultLocaleIdentifier. However, depending on the application,
 * it might be overriden by the application's user settings or auto detection
 * mechanisms.
 *
 * It is recommended that all components which are in need of some information
 * about the locale use the locale object stored in this setting.
 *
 * @F3::FLOW3::Locale::Locale
 */
$c->locale->defaultLocale = NULL;

?>