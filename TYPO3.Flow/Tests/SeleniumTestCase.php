<?php
namespace TYPO3\FLOW3\Tests;

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
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Base Testcase for Selenium Test
 *
 * Probably later phpunit version have better support to set url and other
 * selenium parameters flexible - for now this is done here
 *
 * @api
 */
abstract class SeleniumTestCase extends \PHPUnit_Extensions_SeleniumTestCase {

	/**
	 * Disable the backup and restoration of the $GLOBALS array.
	 */
	protected $backupGlobals = FALSE;

	/**
	 * Enable the backup and restoration of static attributes.
	 */
	protected $backupStaticAttributes = TRUE;

    /**
     * This test is running in a separate PHP process.
     */
    protected $inIsolation = TRUE;

	/**
	 * Set up testcase from XML file
	 *
	 * @return void
	 */
	public function setUp() {
		$settingsPathAndFilename = $this->getSettingsFileName();
		if (!file_exists($settingsPathAndFilename)) {
			$this->markTestSkipped('No Selenium configuration found!');
			return;
		}
		$settings = new \SimpleXMLElement(file_get_contents($settingsPathAndFilename));

		$this->setBrowser((string)$settings->target->browser);
		$this->setHost((string)$settings->target->host);
		$this->setPort((integer)$settings->target->port);
		$this->setTimeout((integer)$settings->target->timeout);
		$this->setBrowserUrl((string)$settings->url);
	}

	/**
	 * Return the file name of the Selenium settings
	 * Override this method if the settings file should be loaded
	 * from another place.
	 *
	 * @api
	 */
	protected function getSettingsFileName() {
		return __DIR__ . '/settings.xml';
	}
	/**
	 * Quick access to selenium commands - doing a click on a element
	 *
	 * @param string $path Selenese selector
	 * @return void
	 */
	protected function clickLink($path) {
		$this->checkElement($path);
		$this->clickAndWait($path);
	}

	/**
	 * Makes an assert to check the existence of a element
	 *
	 * @param string $path Selenese selector
	 * @return void
	 */
	protected function checkElement($path) {
		$this->assertTrue($this->isElementPresent($path), 'element not found: ' . $path);
	}

	/**
	 * Makes an assert to check if a text is present
	 *
	 * @param string $text
	 * @return void
	 */
	protected function checkText($text) {
		$this->assertTrue($this->isTextPresent($text), 'text not found: ' . $text);
	}
}

?>