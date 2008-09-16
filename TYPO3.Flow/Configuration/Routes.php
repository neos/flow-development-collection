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
	->setUrlPattern('[dummy]')
	->setControllerComponentNamePattern('F3::@package::MVC::Controller::@controllerController')
	->setDefaults(
		array(
			'dummy' => 'foo',
			'@package' => 'FLOW3',
			'@controller' => 'Default',
			'@action' => 'index',
		)
	);

/**
 * Default route to map the first three URL segments to package, controller and action
 */
$c->default
	->setUrlPattern('[@package]/[@controller]/[@action]')
	->setDefaults(
		array(
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html'
		)
	);

/**
 * Default route to map the first three URL segments to package, controller and action including optional format-suffix
 */
$c->defaultWithFormat
	->setUrlPattern('[@package]/[@controller]/[@action].[@format]')
	->setDefaults(
		array(
			'@controller' => 'Default',
			'@action' => 'index',
			'@format' => 'html'
		)
	);

?>