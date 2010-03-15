<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Core;

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
 * Testcase for the FLOW3 Bootstrap
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class BootstrapTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeCacheFlushesAllCachesIfTheFLOW3RevisionHasChanged() {
		$mockPackageMetaData = $this->getMock('F3\FLOW3\Package\MetaData', array(), array(), '', FALSE);
		$mockPackageMetaData->expects($this->once())->method('getVersion')->will($this->returnValue('1.2.3 r4566'));

		$mockPackage = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackage->expects($this->once())->method('getPackageMetaData')->will($this->returnValue($mockPackageMetaData));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManager', array(), array(), '', FALSE);
		$mockPackageManager->expects($this->once())->method('getPackage')->with('FLOW3')->will($this->returnValue($mockPackage));

		$mockCoreCache = $this->getMock('F3\FLOW3\Cache\Frontend\FrontendInterface');
		$mockCoreCache->expects($this->once())->method('has')->with('revision')->will($this->returnValue('1.2.3 r4567'));

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array()));

		$mockCacheManager = $this->getMock('F3\FLOW3\Cache\CacheManager', array(), array(), '', FALSE);
		$mockCacheManager->expects($this->once())->method('getCache')->with('FLOW3_Core')->will($this->returnValue($mockCoreCache));
		$mockCacheManager->expects($this->once())->method('flushCaches');

		$mockCacheFactory = $this->getMock('F3\FLOW3\Cache\CacheFactory', array(), array(), '', FALSE);

		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('F3\FLOW3\Cache\CacheManager')->will($this->returnValue($mockCacheManager));
		$mockObjectManager->expects($this->at(1))->method('get')->with('F3\FLOW3\Cache\CacheFactory')->will($this->returnValue($mockCacheFactory));
	
		$bootstrap = $this->getAccessibleMock('F3\FLOW3\Core\Bootstrap', array('dummy'), array(), '', FALSE);
		$bootstrap->_set('objectManager', $mockObjectManager);
		$bootstrap->_set('configurationManager', $mockConfigurationManager);
		$bootstrap->_set('systemLogger', $mockSystemLogger);
		$bootstrap->_set('packageManager', $mockPackageManager);

		$bootstrap->initializeCache();
	}

}
?>