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

/**
 * Default route to map the first three URL segments to package, controller and action
 */
$c->default
	->setUrlPattern('[package]/[controller]/[action]')
	->setDefaults(
		array(
			'package' => 'Default',
			'controller' => 'Default',
			'action' => 'Default',
		)
	);

?>
