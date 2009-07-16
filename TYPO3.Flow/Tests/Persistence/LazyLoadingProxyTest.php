<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * Unit tests for the LazyLoadingProxy
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class LazyLoadingProxyTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lazyLoadingProxyReplacesItselfExactlyOnceInParentOnLoad() {
		$realObject = new \stdClass();
		$closure = function() use ($realObject) { return $realObject; };
		$parent = $this->getMock(uniqid('Parent'), array('FLOW3_AOP_Proxy_getProperty', 'FLOW3_AOP_Proxy_setProperty', 'FLOW3_Persistence_memorizeCleanState'));
		$parent->expects($this->once())->method('FLOW3_AOP_Proxy_setProperty')->with('lazyLoadedProperty', $realObject);
		$proxy = new \F3\FLOW3\Persistence\LazyLoadingProxy($parent, 'lazyLoadedProperty', $closure);
		$parent->expects($this->exactly(3))->method('FLOW3_AOP_Proxy_getProperty')->with('lazyLoadedProperty')->will($this->onConsecutiveCalls($proxy, $realObject, $realObject));
		$parent->lazyLoadedProperty = $proxy;

		$this->assertType('F3\FLOW3\Persistence\LazyLoadingProxy', $parent->lazyLoadedProperty);
		$proxy->_loadRealInstance();
		$proxy->_loadRealInstance();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lazyLoadingProxyCallsMemorizeCleanStateOnParentOnLoad() {
		$realObject = new \stdClass();
		$closure = function() use ($realObject) { return $realObject; };
		$parent = $this->getMock(uniqid('Parent'), array('FLOW3_AOP_Proxy_getProperty', 'FLOW3_AOP_Proxy_setProperty', 'FLOW3_Persistence_memorizeCleanState'));
		$parent->expects($this->once())->method('FLOW3_Persistence_memorizeCleanState')->with('lazyLoadedProperty');
		$proxy = new \F3\FLOW3\Persistence\LazyLoadingProxy($parent, 'lazyLoadedProperty', $closure);
		$parent->expects($this->once())->method('FLOW3_AOP_Proxy_getProperty')->with('lazyLoadedProperty')->will($this->returnValue($proxy));
		$parent->lazyLoadedProperty = $proxy;

		$proxy->_loadRealInstance();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lazyLoadingProxyCallsLoadOnMethodCall() {
		$realObject = new \ArrayObject();
		$closure = function() {};
		$parent = $this->getMock(uniqid('Parent'), array('FLOW3_AOP_Proxy_setProperty', 'FLOW3_Persistence_memorizeCleanState'));
		$proxy = $this->getMock('F3\FLOW3\Persistence\LazyLoadingProxy', array('_loadRealInstance'), array($parent, 'lazyLoadedProperty', $closure));
		$proxy->expects($this->once())->method('_loadRealInstance')->will($this->returnValue($realObject));
		$parent->lazyLoadedProperty = $proxy;

		$parent->lazyLoadedProperty->count();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lazyLoadingProxyCallsLoadOnGet() {
		$realObject = new \stdClass();
		$realObject->foo = 'bar';
		$closure = function() {};
		$parent = $this->getMock(uniqid('Parent'), array('FLOW3_AOP_Proxy_setProperty', 'FLOW3_Persistence_memorizeCleanState'));
		$proxy = $this->getMock('F3\FLOW3\Persistence\LazyLoadingProxy', array('_loadRealInstance'), array($parent, 'lazyLoadedProperty', $closure));
		$proxy->expects($this->once())->method('_loadRealInstance')->will($this->returnValue($realObject));
		$parent->lazyLoadedProperty = $proxy;

		$parent->lazyLoadedProperty->foo;
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lazyLoadingProxyCallsLoadOnSet() {
		$realObject = new \stdClass();
		$closure = function() {};
		$parent = $this->getMock(uniqid('Parent'), array('FLOW3_AOP_Proxy_setProperty', 'FLOW3_Persistence_memorizeCleanState'));
		$proxy = $this->getMock('F3\FLOW3\Persistence\LazyLoadingProxy', array('_loadRealInstance'), array($parent, 'lazyLoadedProperty', $closure));
		$proxy->expects($this->once())->method('_loadRealInstance')->will($this->returnValue($realObject));
		$parent->lazyLoadedProperty = $proxy;

		$parent->lazyLoadedProperty->foo = 'bar';
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lazyLoadingProxyCallsLoadOnIsset() {
		$realObject = new \stdClass();
		$closure = function() {};
		$parent = $this->getMock(uniqid('Parent'), array('FLOW3_AOP_Proxy_setProperty', 'FLOW3_Persistence_memorizeCleanState'));
		$proxy = $this->getMock('F3\FLOW3\Persistence\LazyLoadingProxy', array('_loadRealInstance'), array($parent, 'lazyLoadedProperty', $closure));
		$proxy->expects($this->once())->method('_loadRealInstance')->will($this->returnValue($realObject));
		$parent->lazyLoadedProperty = $proxy;

		isset($parent->lazyLoadedProperty->foo);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lazyLoadingProxyCallsLoadOnUnset() {
		$realObject = new \stdClass();
		$closure = function() {};
		$parent = $this->getMock(uniqid('Parent'), array('FLOW3_AOP_Proxy_setProperty', 'FLOW3_Persistence_memorizeCleanState'));
		$proxy = $this->getMock('F3\FLOW3\Persistence\LazyLoadingProxy', array('_loadRealInstance'), array($parent, 'lazyLoadedProperty', $closure));
		$proxy->expects($this->once())->method('_loadRealInstance')->will($this->returnValue($realObject));
		$parent->lazyLoadedProperty = $proxy;

		unset($parent->lazyLoadedProperty->foo);
	}
}
?>