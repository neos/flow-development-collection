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
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$returnLocaleCallback = function() {
			$args = func_get_args();
			return new \F3\FLOW3\Locale\Locale($args[1]);
		};

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('create')->with('F3\FLOW3\Locale\Locale')->will($this->returnCallback($returnLocaleCallback));

		$findBestMatchingLocaleCallback = function() {
			$args = func_get_args();
			$localeTag = (string)$args[0];

			if (in_array($localeTag, array('en_US_POSIX', 'en_Shaw'))) {
				return new \F3\FLOW3\Locale\Locale('en');
			} else if ($localeTag === 'en_GB') {
				return new \F3\FLOW3\Locale\Locale('en_GB');
			} else if ($localeTag === 'sr_RS') {
				return new \F3\FLOW3\Locale\Locale('sr');
			} else {
				return NULL;
			}
		};

		$mockLocaleCollection = $this->getMock('F3\FLOW3\Locale\LocaleCollectionInterface');
		$mockLocaleCollection->expects($this->any())->method('findBestMatchingLocale')->will($this->returnCallback($findBestMatchingLocaleCallback));

		$mockLocalizationService = $this->getMock('F3\FLOW3\Locale\Service');
		$mockLocalizationService->expects($this->any())->method('getDefaultLocale')->will($this->returnValue(new \F3\FLOW3\Locale\Locale('sv_SE')));

		$this->detector = $this->getAccessibleMock('F3\FLOW3\Locale\Detector', array('dummy'));
		$this->detector->_set('localeBasePath', 'vfs://Foo/');
		$this->detector->injectObjectManager($mockObjectManager);
		$this->detector->injectLocaleCollection($mockLocaleCollection);
		$this->detector->injectLocalizationService($mockLocalizationService);
	}

	/**
	 * Data provider with valid Accept-Language headers and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function localeHeaders() {
		return array(
			array('pl, en-gb;q=0.8, en;q=0.7', new \F3\FLOW3\Locale\Locale('en_GB')),
			array('de, *;q=0.8', new \F3\FLOW3\Locale\Locale('sv_SE')),
			array('pl, de;q=0.5, sr-rs;q=0.1', new \F3\FLOW3\Locale\Locale('sr')),
		);
	}

	/**
	 * @test
	 * @dataProvider localeHeaders
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromHttpHeaderChoosesProperLocale($header, $expectedResult) {
		$locale = $this->detector->detectLocaleFromHttpHeader($header);
		$this->assertEquals($expectedResult, $locale);
	}

	/**
	 * Data provider with valid locale identifiers (tags) and expected results.
	 *
	 * @return array
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function localeTags() {
		return array(
			array('en_GB', new \F3\FLOW3\Locale\Locale('en_GB')),
			array('en_US_POSIX', new \F3\FLOW3\Locale\Locale('en')),
			array('en_Shaw', new \F3\FLOW3\Locale\Locale('en')),
		);
	}

	/**
	 * @test
	 * @dataProvider localeTags
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function detectLocaleFromLocaleTagChoosesProperLocale($tag, $expectedResult) {
		$locale = $this->detector->detectLocaleFromLocaleTag($tag);
		$this->assertEquals($expectedResult, $locale);
	}
}

?>