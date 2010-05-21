<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

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
 */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the Locale Service class.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ServiceTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getLocalizedFilenameWorks() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));

		mkdir('vfs://Foo/Bar/Public/images/', 0777, TRUE);
			// Using touch() doesn't work here - why?
		file_put_contents('vfs://Foo/Bar/Public/images/foobar.en.png', 'FooBar');

		$desiredLocale = new \F3\FLOW3\Locale\Locale('en_GB');
		$parentLocale = new \F3\FLOW3\Locale\Locale('en');
		$defaultLocale = new \F3\FLOW3\Locale\Locale('sv_SE');

		$filename = 'vfs://Foo/Bar/Public/images/foobar.png';
		$expectedFilename = 'vfs://Foo/Bar/Public/images/foobar.en.png';

		$mockLocaleCollection = $this->getMock('F3\FLOW3\Locale\LocaleCollectionInterface');
		$mockLocaleCollection->expects($this->once())->method('findBestMatchingLocale')->with($desiredLocale)->will($this->returnValue($desiredLocale));
		$mockLocaleCollection->expects($this->once())->method('getParentLocaleOf')->with($desiredLocale)->will($this->returnValue($parentLocale));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\Locale\Locale')->will($this->returnValue($defaultLocale));

		$mockSettings = array('locale' => array('defaultLocaleIdentifier' => 'sv_SE'));

		$service = new \F3\FLOW3\Locale\Service();
		$service->injectSettings($mockSettings);
		$service->injectObjectManager($mockObjectManager);
		$service->injectLocaleCollection($mockLocaleCollection);
		$service->initialize();

		$returnedFilename = $service->getLocalizedFilename($filename, $desiredLocale);
		$this->assertEquals($expectedFilename, $returnedFilename);
	}
}

?>