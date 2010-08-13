<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Container;

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
 * Testcase for the abstract Object Container
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractObjectContainerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function globalSettingsCanBeInjected() {
		$settings = array('foo' => 'bar');

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));

		$container->injectSettings($settings);
		$this->assertSame($settings, $container->_get('settings'));

	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createCallsTheClassesCreateMethodPassesTheGivenParametersAndReturnsTheBuiltObject() {
		$objects = array(
			'Foo' => array(
				's' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_PROTOTYPE,
				'm' => '1234'
			)
		);

		$expectedObject = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('c1234'));
		$container->_set('objects', $objects);

		$container->expects($this->at(0))->method('c1234')->with(array('parameter 1', 'parameter 2'))->will($this->returnValue($expectedObject));
		$container->expects($this->at(1))->method('c1234')->will($this->returnValue($expectedObject));
		

		$actualObject = $container->create('Foo', 'parameter 1', 'parameter 2');
		$this->assertSame($expectedObject, $actualObject);

		$actualObject = $container->create('Foo');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObjectException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createThrowsAnExceptionIfTheSpecifiedObjectIsUnknown() {
		$container = $this->getMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->create('Foo');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\WrongScopeException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createThrowsAnExceptionIfTheSpecifiedObjectIsNotOfScopePrototype() {
		$objects = array(
			'Foo' => array('s' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_SINGLETON)
		);

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);
		$container->create('Foo');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCallsTheClassesCreateMethodIfTheRequestedObjectIsOfScopePrototype() {
		$objects = array(
			'Foo' => array(
				's' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_PROTOTYPE,
				'm' => '1234'
			)
		);

		$expectedObject = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('c1234'));
		$container->_set('objects', $objects);

		$container->expects($this->at(0))->method('c1234')->with(array('parameter 1', 'parameter 2'))->will($this->returnValue($expectedObject));
		$container->expects($this->at(1))->method('c1234')->will($this->returnValue($expectedObject));


		$actualObject = $container->get('Foo', 'parameter 1', 'parameter 2');
		$this->assertSame($expectedObject, $actualObject);

		$actualObject = $container->get('Foo');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCreatesAnInstanceIfASingletonHasNotYetBeenInstantiatedAndReturnsTheStoredInstanceIfOneExists() {
		$objects = array(
			'Foo' => array(
				's' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_SINGLETON,
				'm' => '1234'
			)
		);

		$expectedObject = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('c1234'));
		$container->_set('objects', $objects);

		$container->expects($this->once())->method('c1234')->will($this->returnValue($expectedObject));


		$actualObject = $container->get('Foo');
		$this->assertSame($expectedObject, $actualObject);

		$actualObject = $container->get('Foo');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObjectException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getThrowsAnExceptionIfTheSpecifiedObjectIsUnknown() {
		$container = $this->getMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->get('Foo');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObjectException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function recreateThrowsAnExceptionIfTheSpecifiedObjectIsUnknown() {
		$container = $this->getMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->recreate('Foo');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function recreateCreatesAnEmptyInstanceByCallingTheRespectiveMethod() {
		$objects = array(
			'Foo' => array(
				's' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_SINGLETON,
				'm' => '1234'
			)
		);

		$expectedObject = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('r1234'));
		$container->_set('objects', $objects);

		$container->expects($this->once())->method('r1234')->will($this->returnValue($expectedObject));


		$actualObject = $container->recreate('Foo');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObjectException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setInstanceThrowsAnExceptionIfTheSpecifiedObjectIsUnknown() {
		$instance = new \stdClass;
		
		$container = $this->getMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->setInstance('Foo', $instance);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\WrongScopeException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setInstanceThrowsAnExceptionIfTheSpecifiedObjectIsOfScopePrototype() {
		$objects = array(
			'Foo' => array('s' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_PROTOTYPE)
		);

		$instance = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);
		$container->setInstance('Foo', $instance);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setInstanceAllowsForSettingTheInstanceOfASingletonOrSessionObject() {
		$objects = array(
			'Foo' => array('s' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_SINGLETON),
			'Bar' => array('s' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_SESSION)
		);

		$fooInstance = new \stdClass;
		$barInstance = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);

		$container->setInstance('Foo', $fooInstance);
		$container->setInstance('Bar', $barInstance);

		$objects = $container->_get('objects');

		$this->assertSame($fooInstance, $objects['Foo']['i']);
		$this->assertSame($barInstance, $objects['Bar']['i']);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isRegisteredTellsIfAnObjectIsKnownToTheContainer() {
		$objects = array(
			'Foo' => array(),
			'Bar' => array()
		);

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);

		$this->assertTrue($container->isRegistered('Foo'));
		$this->assertTrue($container->isRegistered('Bar'));
		$this->assertFalse($container->isRegistered('Baz'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitiveObjectNameReturnsTheCorrectCaseOfAnObjectNameIfOnlyTheCaseInsensitiveNameIsKnown() {
		$objects = array(
			'FooBi' => array('l' => 'foobi'),
			'BarFo' => array('l' => 'barfo')
		);

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);

		$this->assertSame('FooBi', $container->getCaseSensitiveObjectName('foobi'));
		$this->assertSame('BarFo', $container->getCaseSensitiveObjectName('BARfo'));
		$this->assertFalse($container->getCaseSensitiveObjectName('UnkNoWn'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectNameByClassNameReturnsTheNameOfTheFirstObjectWhichHasTheGivenClassNameConfigured() {
		$objects = array(
			'Foo' => array('c' => 'FooImplementation'),
			'Bar' => array(),
		);

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);

		$this->assertSame('Foo', $container->getObjectNameByClassName('FooImplementation'));
		$this->assertSame('Bar', $container->getObjectNameByClassName('Bar'));
		$this->assertFalse($container->getObjectNameByClassName('Baz'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassNameByObjectNameReturnsConfiguredImplementationClassName() {
		$objects = array(
			'Foo' => array('c' => 'FooImplementation'),
			'Bar' => array(),
		);

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);

		$this->assertSame('FooImplementation', $container->getClassNameByObjectName('Foo'));
		$this->assertSame('Bar', $container->getClassNameByObjectName('Bar'));
		$this->assertFalse($container->getClassNameByObjectName('Baz'));
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObjectException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScopeThrowsAnExceptionIfTheSpecifiedObjectIsUnknown() {
		$container = $this->getMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->getScope('Foo');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScopeReturnsTheScopeOfTheGivenObject() {
		$objects = array(
			'Foo' => array('s' => \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SESSION),
		);

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);

		$this->assertSame(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_SESSION, $container->getScope('Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPrototypeIsOptimizedForReturningPrototypesToTheObjectContainerInternally() {
		$objects = array(
			'Foo' => array(
				's' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_PROTOTYPE,
				'm' => '1234'
			)
		);

		$expectedObject = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('c1234'));
		$container->_set('objects', $objects);

		$container->expects($this->at(0))->method('c1234')->with(array('parameter 1', 'parameter 2'))->will($this->returnValue($expectedObject));


		$actualObject = $container->_call('getPrototype', 'Foo', array('parameter 1', 'parameter 2'));
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSingletonIsOptimizedForReturningSingletonsToTheObjectContainerInternally() {
		$objects = array(
			'Foo' => array(
				's' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_PROTOTYPE,
				'm' => '1234'
			)
		);

		$expectedObject = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('c1234'));
		$container->_set('objects', $objects);

		$container->expects($this->once())->method('c1234')->will($this->returnValue($expectedObject));


		$actualObject = $container->_call('getSingleton', 'Foo');
		$this->assertSame($expectedObject, $actualObject);

		$actualObject = $container->_call('getSingleton', 'Foo');
		$this->assertSame($expectedObject, $actualObject);
	}


}
?>