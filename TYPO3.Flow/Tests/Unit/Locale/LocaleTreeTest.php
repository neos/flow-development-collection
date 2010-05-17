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

/**
 * Testcase for the LocaleTree class
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @todo write more tests
 */
class LocaleTreeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var array An array of \F3\FLOW3\Locale\Locale instances
	 */
	protected $locales;

	/**
	 * @var \F3\FLOW3\Locale\LocaleTree
	 */
	protected $tree;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->locales = array(
			new \F3\FLOW3\Locale\Locale('en'),
			new \F3\FLOW3\Locale\Locale('pl_PL'),
			new \F3\FLOW3\Locale\Locale('de'),
			new \F3\FLOW3\Locale\Locale('pl'),
		);

		$returnLocaleNodeCallback = function() {
			$args = func_get_args();
			if (isset($args[1]) === FALSE) $args[1] = NULL;
			return new \F3\FLOW3\Locale\LocaleNode($args[1]);
		};

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback($returnLocaleNodeCallback));

		$this->tree = new \F3\FLOW3\Locale\LocaleTree($mockObjectManager);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function addLocaleWorks() {
		foreach ($this->locales as $locale) {
			$this->tree->addLocale($locale);
		}

		/** @todo I don't know why asserSame doesn't work here yet **/
		$this->assertEquals($this->locales[3], $this->tree->getParentLocaleOf($this->locales[1]));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function findBestMatchingLocaleWorks() {
		foreach ($this->locales as $locale) {
			$this->tree->addLocale($locale);
		}

		$this->assertEquals($this->locales[1], $this->tree->findBestMatchingLocale($this->locales[1]));
		$this->assertEquals($this->locales[1], $this->tree->findBestMatchingLocale(new \F3\FLOW3\Locale\Locale('pl_PL_DVORAK')));
		$this->assertEquals(NULL, $this->tree->findBestMatchingLocale(new \F3\FLOW3\Locale\Locale('sv')));
	}
}
?>