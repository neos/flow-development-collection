<?php
namespace TYPO3\FLOW3\Tests\Functional\I18n\Cldr;

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
 * Testcase for the I18N CLDR Repository
 *
 */
class CldrRepositoryTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\FLOW3\I18n\Cldr\CldrRepository
	 */
	protected $cldrRepository;

	/**
	 * @var string
	 */
	protected $cldrBasePath;

	/**
	 * Initialize dependencies
	 */
	public function setUp() {
		parent::setUp();
		$this->cldrRepository = $this->objectManager->get('TYPO3\FLOW3\I18n\Cldr\CldrRepository');

		$this->cldrBasePath = $this->retrieveCldrBasePath();
	}

	/**
	 * Retrieves the base path from the CldrRepository's cldrBasePath attribute
	 * @return string
	 */
	protected function retrieveCldrBasePath() {
		$reflectedCldrRepository = new \ReflectionObject($this->cldrRepository);
		$reflectedBasePathProperty = $reflectedCldrRepository->getProperty('cldrBasePath');
		$reflectedBasePathProperty->setAccessible(true);

		return $reflectedBasePathProperty->getValue($this->cldrRepository);
	}

	/**
	 * @test
	 */
	public function modelIsReturnedCorrectlyForLocaleImplicatingChaining() {
		$localeImplementingChaining = new \TYPO3\FLOW3\I18n\Locale('de_DE');

		$cldrModel = $this->cldrRepository->getModelForLocale($localeImplementingChaining);

		$this->assertAttributeContains(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, 'main/root.xml')), 'sourcePaths', $cldrModel);
		$this->assertAttributeContains(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, 'main/de_DE.xml')), 'sourcePaths', $cldrModel);
		$this->assertAttributeContains(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->cldrBasePath, 'main/de.xml')), 'sourcePaths', $cldrModel);
	}

}
?>