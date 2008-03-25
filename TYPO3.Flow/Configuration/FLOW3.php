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
 * Enable or disable caching of the component configurations. If caching is
 * enabled, a cache backend must be properly configured.
 *
 * @type boolean
 */
$c->componentConfigurationCache->enable = TRUE;

/**
 * Define the backend used for caching component configurations. Specify the
 * name of a component implementing the F3_FLOW3_Cache_BackendInterface.
 *
 * @type F3_FLOW3_Cache_BackendInterface
 */
$c->componentConfigurationCache->backend = 'F3_FLOW3_Cache_Backend_File';

/**
 * Options which are passed the cache backend.
 *
 * @type array
 */
$c->componentConfigurationCache->backendOptions = array();

/**
 * Defines the global, last-resort exception handler.
 *
 * @type F3_FLOW3_Error_ExceptionHandlerInterface
 */
$c->exceptionHandler = 'F3_FLOW3_Error_QuietExceptionHandler';

?>