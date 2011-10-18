<?php
namespace TYPO3\FLOW3\Tests\Unit\I18n\Xml;

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
 * Testcase for the AbstractXmlModel class
 *
 */
class AbstractXmlModelTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function modelCallsParserIfNoCacheAvailable() {
		$mockFilenamePath = 'foo';
		$mockParsedData = 'bar';

		$mockCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->once())->method('has')->with(md5($mockFilenamePath))->will($this->returnValue(FALSE));
		$mockCache->expects($this->once())->method('set')->with(md5($mockFilenamePath), $mockParsedData);

		$mockParser = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Xml\AbstractXmlParser', array('getParsedData', 'doParsingFromRoot'));
		$mockParser->expects($this->once())->method('getParsedData')->with($mockFilenamePath)->will($this->returnValue($mockParsedData));

		$model = $this->getAccessibleMock('TYPO3\FLOW3\I18n\Xml\AbstractXmlModel', array('dummy'), array($mockFilenamePath));
		$model->injectCache($mockCache);
		$model->_set('xmlParser', $mockParser);
		$model->initializeObject();
		$model->shutdownObject();
	}
}

?>