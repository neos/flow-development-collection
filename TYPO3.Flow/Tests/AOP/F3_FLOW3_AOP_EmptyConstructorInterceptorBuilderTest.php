<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * Testcase for the AOP Empty Constructor Interceptor Builder
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class EmptyConstructorInterceptorBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildRendersCodeOfAPlaceHolderMethod() {
		$className = uniqid('TestClass');
		eval('
			class ' . $className . ' {
				public function __construct($arg1, array $arg2, \ArrayObject $arg3, $arg4= "__construct", $arg5 = TRUE) {}
			}
		');

		$interceptedMethods = array(
			'__construct' => array(
				'groupedAdvices' => array('groupedAdvicesDummy'),
				'declaringClassName' => $className
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->initialize(array($className));

		$expectedCode = '
	/**
	 * Non-advised constructor interceptor.
	 * ' . '
	 * @return void
	 */
	public function __construct(PARAMETERSCODE1, \F3\FLOW3\Object\ManagerInterface $AOPProxyObjectManager, \F3\FLOW3\Object\FactoryInterface $AOPProxyObjectFactory) {
		$this->objectManager = $AOPProxyObjectManager;
		$this->objectFactory = $AOPProxyObjectFactory;
		$this->AOPProxyDeclareMethodsAndAdvices();
		parent::__construct(PARAMETERSCODE2);
	}
';

		$builder = $this->getMock('F3\FLOW3\AOP\EmptyConstructorInterceptorBuilder', array('buildMethodParametersCode'), array(), '', FALSE);
		$builder->injectReflectionService($mockReflectionService);
		$builder->expects($this->at(0))->method('buildMethodParametersCode')->with($className, '__construct', TRUE)->will($this->returnValue('PARAMETERSCODE1'));
		$builder->expects($this->at(1))->method('buildMethodParametersCode')->with($className, '__construct', FALSE)->will($this->returnValue('PARAMETERSCODE2'));

		$actualCode = $builder->build('__construct', $interceptedMethods, 'Bar');
		$this->assertSame($expectedCode, $actualCode);
	}
}
?>