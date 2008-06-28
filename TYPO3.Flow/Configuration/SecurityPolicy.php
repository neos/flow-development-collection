<?php
declare(ENCODING="utf-8");

/*                                                                        *
 * Configuration for the FLOW3 Security Policy                            *
 *                                                                        *
 * This file contains the default base security policy for the FLOW3      *
 * Framework. Don't modify this file but add configuration options to     *
 * the SecurityPolicy.php file in the in global Configuration/ directory  *
 * instead.                                                               *
 *                                                                        */

/**
 * @package FLOW3
 *
 * @version $Id:$
 */

$resources['deleteMethods'] = 'method(.*->delete.*())';
$resources['MyPackageUpdateMethods'] = 'method(F3_MyPackage_.*->update.*())';
$resources['dataManipulationMethods'] = 'deleteMethods || MyPackageUpdateMethods';

$roles['ADMINISTRATOR'] = array();
$roles['DEVELOPER'] = array();
$roles['CUSTOMER'] = array();
//Roles that are child roles of CUSTOMER
$roles['PRIVILEGED_CUSTOMER'] = array('CUSTOMER');
$roles['NEWSLETTER_SUBSCRIPTION_CUSTOMER'] = array('CUSTOMER');

//Note: A privilege has always a unique identifier (prefix your package key for custom privileges) and one of the suffixes _GRANT or _DENY
$acls['ADMINISTRATOR'] = array(
			'deleteMethods' => 'ACCESS_GRANT',
			'MyPackageUpdateMethods' => 'ACCESS_DENY'
		);
$acls['CUSTOMER'] = array(
			'deleteMethods' => 'ACCESS_DENY',
			'MyPackageUpdateMethods' => 'ACCESS_DENY'
		);
$acls['PRIVILEGED_CUSTOMER'] = array(
			'deleteMethods' => 'ACCESS_DENY',
			'MyPackageUpdateMethods' => 'ACCESS_GRANT'
		);

$c->security->policy->resources = $resources;
$c->security->policy->roles = $roles;
$c->security->policy->acls = $acls;

?>