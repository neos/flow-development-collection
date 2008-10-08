<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Routes configuration                                                   *
 *                                                                        *
 * This file contains the configuration for the MVC router.               *
 * Just add your own modifications as necessary.                          *
 *                                                                        *
 * Please refer to the FLOW3 manual for possible configuration options.   *
 *                                                                        */

$c->fallback
	->setUriPattern('')
	->setControllerComponentNamePattern('F3::@package::MVC::Controller::@controllerController')
	->setDefaults(
		array(
			'@package' => 'FLOW3',
			'@controller' => 'Default',
			'@action' => 'index',
		)
	);

/**
 * Default route to map the first three URL segments to package, controller and action
 */
$c->default
	->setUriPattern('[@package]/[@controller]/[@action]')
	->setDefaults(
		array(
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html'
		)
	);

/**
 * Default route with just the package
 */
$c->defaultWithPackage
	->setUriPattern('[@package]')
	->setDefaults(
		array(
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html',
			'@demo' => ''
		)
	);

/**
 * Default route to map the first three URL segments to package, controller and action including optional format-suffix
 */
$c->defaultWithFormat
	->setUriPattern('[@package]/[@controller]/[@action].[@format]')
	->setDefaults(
		array(
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html'
		)
	);

?>
