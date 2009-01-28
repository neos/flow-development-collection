<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\SignalSlot;

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

/**
 * @package FLOW3
 * @subpackage SignalSlot
 * @version $Id$
 */

/**
 * A dispatcher which dispatches signals by calling its registered slot methods
 * and passing them the method arguments which were originally passed to the
 * signal method.
 *
 * @package FLOW3
 * @subpackage SignalSlot
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Dispatcher {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Information about all slots connected a certain signal.
	 * Indexed by [$signalClassName][$signalMethodName] and then numeric with an
	 * array of information about the slot
	 * @var array
	 */
	protected $slots = array();

	/**
	 * Injects the object manager
	 *
	 * @param ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Connects a signal with a slot.
	 * One slot can be connected with multiple signals by calling this method multiple times.
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalMethodName Method name of the signal
	 * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
	 * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
	 * @param boolean $omitSignalInformation If set to TRUE, the first argument passed to the slot will be the first argument of the signal instead of some information about the signal.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connect($signalClassName, $signalMethodName, $slotClassNameOrObject, $slotMethodName = '', $omitSignalInformation = FALSE) {
		$class = NULL;
		$method = NULL;
		$object = NULL;

		if (is_object($slotClassNameOrObject)) {
			$object = $slotClassNameOrObject;
			$method = ($slotClassNameOrObject instanceof \Closure) ? '__invoke' : $slotMethodName;
		} else {
			if ($slotMethodName === '') throw new InvalidArgumentException('The slot method name must not be empty if a slot class name was specified.', 1229531659);
			$class = $slotClassNameOrObject;
			$method = $slotMethodName;
		}

		$this->slots[$signalClassName][$signalMethodName][] = array(
			'class' => $class,
			'method' => $method,
			'object' => $object,
			'omitSignalInformation' => ($omitSignalInformation === TRUE)
		);
	}

	/**
	 * Dispatches a signal by calling the registered Slot methods
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalMethodName Method name of the signal
	 * @param array $signalArguments arguments passed to the signal method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatch($signalClassName, $signalMethodName, array $signalArguments) {
	if (!isset($this->slots[$signalClassName][$signalMethodName])) return;
		$this->systemLogger->log(sprintf('Dispatching signal %s::%s ...', $signalClassName, $signalMethodName), LOG_DEBUG);
		foreach ($this->slots[$signalClassName][$signalMethodName] as $slotInformation) {
			$object = (isset($slotInformation['object'])) ? $slotInformation['object'] : $this->objectManager->getObject($slotInformation['class']);
			$slotArguments = $signalArguments;
			if ($slotInformation['omitSignalInformation'] !== TRUE) array_unshift($slotArguments, $signalClassName . '::' . $signalMethodName);
			$this->systemLogger->log(sprintf('  to slot %s::%s.', get_class($object), $slotInformation['method']), LOG_DEBUG);
			call_user_func_array(array($object, $slotInformation['method']), $slotArguments);
		}
	}

	/**
	 * Returns all slots which are connected with the given signal
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalMethodName Method name of the signal
	 * @return array An array of arrays with slot information
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSlots($signalClassName, $signalMethodName) {
		return (isset($this->slots[$signalClassName][$signalMethodName])) ? $this->slots[$signalClassName][$signalMethodName] : array();
	}

}
?>