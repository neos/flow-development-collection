<?php
namespace Neos\Flow\Tests\Functional\I18n\Cldr;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n;
use Neos\Flow\I18n\Cldr\CldrRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\Files;

/**
 * Testcase for the I18N CLDR Repository
 *
 */
class CldrRepositoryTest extends FunctionalTestCase
{
    /**
     * @var CldrRepository
     */
    protected $cldrRepository;

    /**
     * @var string
     */
    protected $cldrBasePath;

    /**
     * Initialize dependencies
     */
    public function setUp()
    {
        parent::setUp();
        $this->cldrRepository = $this->objectManager->get(CldrRepository::class);

        $this->cldrBasePath = $this->retrieveCldrBasePath();
    }

    /**
     * Retrieves the base path from the CldrRepository's cldrBasePath attribute
     * @return string
     */
    protected function retrieveCldrBasePath()
    {
        $reflectedCldrRepository = new \ReflectionObject($this->cldrRepository);
        $reflectedBasePathProperty = $reflectedCldrRepository->getProperty('cldrBasePath');
        $reflectedBasePathProperty->setAccessible(true);

        return $reflectedBasePathProperty->getValue($this->cldrRepository);
    }

    /**
     * @test
     */
    public function modelIsReturnedCorrectlyForLocaleImplicatingChaining()
    {
        $localeImplementingChaining = new I18n\Locale('de_DE');

        $cldrModel = $this->cldrRepository->getModelForLocale($localeImplementingChaining);

        $this->assertAttributeContains(Files::concatenatePaths([$this->cldrBasePath, 'main/root.xml']), 'sourcePaths', $cldrModel);
        $this->assertAttributeContains(Files::concatenatePaths([$this->cldrBasePath, 'main/de_DE.xml']), 'sourcePaths', $cldrModel);
        $this->assertAttributeContains(Files::concatenatePaths([$this->cldrBasePath, 'main/de.xml']), 'sourcePaths', $cldrModel);
    }
}
