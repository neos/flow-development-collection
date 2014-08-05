<?php
namespace TYPO3\Flow\Tests\Unit\Object\Proxy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 *
 */
class ProxyClassTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @return array
	 */
	public function proxyClassesDataProvider() {
		return array(
			array(
				'originalClassName' => '\Acme\Namespace\ClassName',
				'originalClassAnnotations' => array(),
				'originalClassDocumentation' => '',
				'originalClassConstants' => array(array('name' => 'TEST_CONSTANT', 'value' => '1')),
				'expectedProxyCode' => "namespace \Acme\Namespace;\n".
			"\n" .
			"use Doctrine\\ORM\\Mapping as ORM;\n" .
			"use TYPO3\\Flow\\Annotations as Flow;\n" .
			"\n" .
			"class ClassName extends ClassName".\TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX." implements \TYPO3\Flow\Object\Proxy\ProxyInterface {\n\n" .
			"	const TEST_CONSTANT = 1;\n\n".
			"}",
			),
			array(
				'originalClassName' => '\ClassWithoutNamespace',
				'originalClassAnnotations' => array(),
				'originalClassDocumentation' => '',
				'originalClassConstants' => array(array('name' => 'TEST_CONSTANT', 'value' => '1')),
				'expectedProxyCode' =>
			"use Doctrine\\ORM\\Mapping as ORM;\n" .
			"use TYPO3\\Flow\\Annotations as Flow;\n" .
			"\n" .
			"class ClassWithoutNamespace extends ClassWithoutNamespace".\TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX." implements \TYPO3\Flow\Object\Proxy\ProxyInterface {\n\n" .
			"	const TEST_CONSTANT = 1;\n\n".
			"}",
			),
			array(
				'originalClassName' => 'ClassWithoutNamespace',
				'originalClassAnnotations' => array(),
				'originalClassDocumentation' => '',
				'originalClassConstants' => array(array('name' => 'TEST_CONSTANT', 'value' => '1')),
				'expectedProxyCode' =>
			"use Doctrine\\ORM\\Mapping as ORM;\n" .
			"use TYPO3\\Flow\\Annotations as Flow;\n" .
			"\n" .
			"class ClassWithoutNamespace extends ClassWithoutNamespace".\TYPO3\Flow\Object\Proxy\Compiler::ORIGINAL_CLASSNAME_SUFFIX." implements \TYPO3\Flow\Object\Proxy\ProxyInterface {\n\n" .
			"	const TEST_CONSTANT = 1;\n\n".
			"}",
			),
		);
	}

	/**
	 * @test
	 * @dataProvider proxyClassesDataProvider
	 */
	public function renderWorksAsExpected($originalClassName, $originalClassAnnotations, $originalClassDocumentation, $originalClassConstants, $expectedProxyCode) {
		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isClassAbstract')->will($this->returnValue(strpos($expectedProxyCode, 'abstract ') !== FALSE));
		$mockReflectionService->expects($this->any())->method('getClassAnnotations')->will($this->returnValue($originalClassAnnotations));

		$mockProxyClass = $this->getAccessibleMock('TYPO3\Flow\Object\Proxy\ProxyClass', array('buildClassDocumentation'), array($originalClassName), '', TRUE);
		$mockProxyClass->expects($this->any())->method('buildClassDocumentation')->will($this->returnValue($originalClassDocumentation));
		$mockProxyClass->injectReflectionService($mockReflectionService);
		foreach ($originalClassConstants as $originalClassConstant) {
			$mockProxyClass->addConstant($originalClassConstant['name'], $originalClassConstant['value']);
		}

		$proxyCode = $mockProxyClass->render();

		$this->assertEquals($expectedProxyCode, $proxyCode);
	}

}