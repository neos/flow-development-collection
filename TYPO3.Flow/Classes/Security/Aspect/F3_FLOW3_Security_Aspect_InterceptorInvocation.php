<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Aspect;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * The central security aspect, that invoces the security interceptors.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @aspect
 */
class InterceptorInvocation {

	/**
	 * The policy enforcement advice. This advices applies the security enforcement interceptor to all methods configured in the policy.
	 *
	 * @around filter(F3::FLOW3::Security::ACL::PolicyService)
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicy() {
		//Asks the policy service to return the roles for this joinpoint (it will have a cache to speed this up)
		//Set the joinpoint in the interceptor
		//invoke the policy enforcement interceptor
		//$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		//invoke the after invocation interceptor
		//return $result;
	}
}

?>