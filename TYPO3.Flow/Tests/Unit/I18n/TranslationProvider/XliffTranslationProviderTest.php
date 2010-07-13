<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\TranslationProvider;

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
 * Testcase for the XliffTranslationProvider
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class XliffTranslationProviderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider
	 */
	protected $translationProvider;

	/**
	 * @var string
	 */
	protected $dummyFilename;

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $dummyLocale;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->dummyFilename = 'foo';
		$this->dummyLocale = new \F3\FLOW3\I18n\Locale('en_GB');

		$mockPluralsReader = $this->getMock('F3\FLOW3\I18n\Cldr\Reader\PluralsReader');
		$mockPluralsReader->expects($this->once())->method('getPluralForms')->with($this->dummyLocale)->will($this->returnValue(array('one', 'other')));

		$this->translationProvider = $this->getAccessibleMock('F3\FLOW3\I18n\TranslationProvider\XliffTranslationProvider', array('getModel'));
		$this->translationProvider->injectPluralsReader($mockPluralsReader);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getTranslationByOriginalLabelWorks() {
		$mockModel = $this->getMock('F3\FLOW3\I18n\Xliff\XliffModel');
		$mockModel->expects($this->once())->method('getTargetBySource')->with('bar', 0)->will($this->returnValue('baz'));
		$this->translationProvider->expects($this->once())->method('getModel')->with($this->dummyFilename, $this->dummyLocale)->will($this->returnValue($mockModel));

		$result = $this->translationProvider->getTranslationByOriginalLabel($this->dummyFilename, 'bar', $this->dummyLocale, 'one');
		$this->assertEquals('baz', $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getTranslationByIdWorks() {
		$mockModel = $this->getMock('F3\FLOW3\I18n\Xliff\XliffModel');
		$mockModel->expects($this->once())->method('getTargetByTransUnitId')->with('bar', 1)->will($this->returnValue('baz'));
		$this->translationProvider->expects($this->once())->method('getModel')->with($this->dummyFilename, $this->dummyLocale)->will($this->returnValue($mockModel));

		$result = $this->translationProvider->getTranslationById($this->dummyFilename, 'bar', $this->dummyLocale);
		$this->assertEquals('baz', $result);
	}
}

?>
