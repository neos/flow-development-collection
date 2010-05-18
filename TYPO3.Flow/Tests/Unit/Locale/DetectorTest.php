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
 *                                                                        */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the Locale Detector
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DetectorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Locale\Detector
	 */
	protected $detector;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\Locale\LocaleTreeInterface
	 */
	protected $mockTree;

	/**
	 * @var string
	 */
	protected $mockSettings;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));

		foreach (array('en', 'sr_Cyrl_RS', 'en_GB', 'sr') as $localeIdentifier) {
			mkdir('vfs://Foo/Bar/Private/Locale/' . $localeIdentifier, 0777, TRUE);
		}

		$returnLocaleNodeCallback = function() {
			$args = func_get_args();
			if (isset($args[1]) === FALSE) $args[1] = NULL;
			return new \F3\FLOW3\Locale\LocaleNode($args[1]);
		};

		$mockObjectManagerForTree = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManagerForTree->expects($this->any())->method('create')->with('F3\FLOW3\Locale\LocaleNode')->will($this->returnCallback($returnLocaleNodeCallback));
		$this->mockTree = new \F3\FLOW3\Locale\LocaleTree($mockObjectManagerForTree);

		$returnLocaleCallback = function() {
			$args = func_get_args();
			return new \F3\FLOW3\Locale\Locale($args[1]);
		};

		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->mockObjectManager->expects($this->any())->method('create')->with('F3\FLOW3\Locale\Locale')->will($this->returnCallback($returnLocaleCallback));

		$mockPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockPackage->expects($this->any())->method('getPackageKey')->will($this->returnValue('Bar'));
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue(array($mockPackage)));

		$this->mockSettings = array('locale' => array('defaultLocale' => new \F3\FLOW3\Locale\Locale('sv_SE')));

		$this->detector = $this->getAccessibleMock('F3\FLOW3\Locale\Detector', array('dummy'));
		$this->detector->_set('localeBasePath', 'vfs://Foo/');
		$this->detector->injectObjectManager($this->mockObjectManager);
		$this->detector->injectPackageManager($mockPackageManager);
		$this->detector->injectSettings($this->mockSettings);
		$this->detector->injectLocaleTree($this->mockTree);
		$this->detector->initializeObject();
	}

	/**
	 * Data provider with valid Accept-Language headers and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function localeHeaders() {
		return array(
			array('pl, en-gb;q=0.8, en;q=0.7', array('language' => 'en', 'region' => 'GB', 'script' => NULL)),
			array('de, *;q=0.8', array('language' => 'sv', 'region' => 'SE', 'script' => NULL)),
			array('pl, de;q=0.5, sr-rs;q=0.1', array('language' => 'sr', 'region' => NULL, 'script' => NuLL)),
		);
	}

	/**
	 * @test
	 * @dataProvider localeHeaders
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromHttpHeaderChoosesProperLocale($header, array $expectedResult) {
		$locale = $this->detector->detectLocaleFromHttpHeader($header);
		$this->assertEquals($expectedResult['language'], $locale->getLanguage());
		$this->assertEquals($expectedResult['region'], $locale->getRegion());
		$this->assertEquals($expectedResult['script'], $locale->getScript());
	}

	/**
	 * Data provider with valid locale identifiers (tags) and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function localeTags() {
		return array(
			array('en_GB', array('language' => 'en', 'region' => 'GB', 'script' => NULL)),
			array('en_US_POSIX', array('language' => 'en', 'region' => NULL, 'script' => NULL)),
			array('en_Shaw', array('language' => 'en', 'region' => NULL, 'script' => NULL)),
		);
	}

	/**
	 * @test
	 * @dataProvider localeTags
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromLocaleTagChoosesProperLocale($tag, $expectedResult) {
		$locale = $this->detector->detectLocaleFromLocaleTag($tag);
		$this->assertEquals($expectedResult['language'], $locale->getLanguage());
		$this->assertEquals($expectedResult['region'], $locale->getRegion());
		$this->assertEquals($expectedResult['script'], $locale->getScript());
	}

	/**
	 * Data provider with package name, locale-folder name, and expected result
	 * (an array with one Locale object).
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function localeFolderInPackage() {
		return array(
			array('FLOW3', 'en_GB', new \F3\FLOW3\Locale\Locale('en_GB')),
			array('Fluid', 'ha_Arab_SD', new \F3\FLOW3\Locale\Locale('ha_Arab_SD')),
		);
	}

	/**
	 * @test
	 * @dataProvider localeFolderInPackage
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function readingLocalesFromFilesystemWorksForOneLocale($packageKey, $localeFolder, $expectedResult) {
		mkdir('vfs://Foo/' . $packageKey . '/Private/Locale/' . $localeFolder, 0777, TRUE);

		$mockPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockPackage->expects($this->exactly(2))->method('getPackageKey')->will($this->returnValue($packageKey));

		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface');
		$mockPackageManager->expects($this->once())->method('getActivePackages')->will($this->returnValue(array($mockPackage)));

		$settings = $this->mockSettings;
		$settings['locale']['automaticSearchForAvailableLocales'] = TRUE;

		$this->detector = $this->getAccessibleMock('F3\FLOW3\Locale\Detector', array('dummy'));
		$this->detector->_set('localeBasePath', 'vfs://Foo/');
		$this->detector->injectPackageManager($mockPackageManager);
		$this->detector->injectObjectManager($this->mockObjectManager);
		$this->detector->injectSettings($settings);
		$this->detector->injectLocaleTree($this->mockTree);
		$this->detector->initializeObject();

		$foundLocale = $this->detector->detectLocaleFromLocaleTag($localeFolder);
		$this->assertEquals($expectedResult, $foundLocale);

		rmdir('vfs://Foo/' . $packageKey . '/Private/Locale/' . $localeFolder);
	}
}
?>