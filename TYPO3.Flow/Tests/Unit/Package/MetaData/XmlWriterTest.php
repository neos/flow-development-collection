<?php
namespace TYPO3\FLOW3\Tests\Unit\Package\MetaData;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the XML MetaData writer
 *
 */
class XmlWriterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));
	}

	/**
	 * @test
	 */
	public function testWritePackageMetaDataCreatesXml() {
		$packageMetaDataPath = \vfsStream::url('testDirectory') . '/';

		$mockPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');
		$mockPackage->expects($this->once())->method('getMetaPath')->will($this->returnValue($packageMetaDataPath));

		$meta = new \TYPO3\FLOW3\Package\MetaData('Acme.YetAnotherTestPackage');
		$meta->setTitle('Yet another test package');
		$meta->setDescription('A test package to test the creation of the Package.xml by the Package Manager');
		$meta->setVersion('0.1.1');
		$meta->addCategory('Testing');
		$meta->addCategory('System');
		$meta->addParty(new \TYPO3\FLOW3\Package\MetaData\Person('LeadDeveloper', 'Robert Lemke', 'robert@typo3.org', 'http://www.flow3.org', 'TYPO3 Association', 'robert'));
		$meta->addParty(new \TYPO3\FLOW3\Package\MetaData\Company(null, 'Acme Inc.', 'info@acme.com', 'http://www.acme.com'));
		$meta->addConstraint(new \TYPO3\FLOW3\Package\MetaData\PackageConstraint('depends', 'TYPO3.FLOW3', '1.0.0', '1.9.9'));
		$meta->addConstraint(new \TYPO3\FLOW3\Package\MetaData\SystemConstraint('depends', 'PHP', NULL, '5.3.0'));
		$meta->addConstraint(new \TYPO3\FLOW3\Package\MetaData\SystemConstraint('suggests', 'Memory', '16M'));

		$metaWriter = new \TYPO3\FLOW3\Package\MetaData\XmlWriter();
		$metaWriter->writePackageMetaData($mockPackage, $meta);
		$this->assertXmlFileEqualsXmlFile($packageMetaDataPath . 'Package.xml', __DIR__ . '/../Fixtures/XmlWriterTest/Package.xml');
	}
}
?>