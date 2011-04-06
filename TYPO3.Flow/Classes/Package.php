<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \F3\FLOW3\Package\Package as BasePackage;

/**
 * The FLOW3 Package
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Package extends BasePackage {

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \F3\FLOW3\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\F3\FLOW3\Core\Bootstrap $bootstrap) {
		require_once(__DIR__ . '/../Resources/PHP/AutoLoader.php');

		$bootstrap->registerCompiletimeCommandController('flow3:object');
		$bootstrap->registerCompiletimeCommandController('flow3:core');
		$bootstrap->registerCompiletimeCommandController('flow3:cache');

		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect('F3\FLOW3\Core\Bootstrap', 'finishedRuntimeRun', 'F3\FLOW3\Persistence\PersistenceManagerInterface', 'persistAll');
		$dispatcher->connect('F3\FLOW3\Core\Bootstrap', 'bootstrapShuttingDown', 'F3\FLOW3\Configuration\ConfigurationManager', 'shutdown');
		$dispatcher->connect('F3\FLOW3\Core\Bootstrap', 'bootstrapShuttingDown', 'F3\FLOW3\Object\ObjectManagerInterface', 'shutdown');
		$dispatcher->connect('F3\FLOW3\Core\Bootstrap', 'bootstrapShuttingDown', 'F3\FLOW3\Reflection\ReflectionService', 'saveToCache');

		$dispatcher->connect('F3\FLOW3\Command\CoreCommandController', 'finishedCompileCommand', 'F3\FLOW3\Persistence\Doctrine\PersistenceManager', 'compile');
		$dispatcher->connect('F3\FLOW3\Command\CoreCommandController', 'finishedCompileCommand', 'F3\FLOW3\Security\Policy\PolicyService', 'savePolicyCache');
	}
}

?>