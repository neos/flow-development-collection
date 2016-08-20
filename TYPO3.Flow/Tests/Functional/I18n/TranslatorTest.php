<?php
namespace TYPO3\Flow\Tests\Functional\I18n;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\I18n;

/**
 * Testcase for the I18N translations
 *
 */
class TranslatorTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @var \TYPO3\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * Initialize dependencies
     */
    public function setUp()
    {
        parent::setUp();
        $this->translator = $this->objectManager->get(\TYPO3\Flow\I18n\Translator::class);
    }

    /**
     * @return array
     */
    public function idAndLocaleForTranslation()
    {
        return array(
            array('authentication.username', new I18n\Locale('en'), 'Username'),
            array('authentication.username', new I18n\Locale('de_CH'), 'Benutzername'),
            array('update', new I18n\Locale('en'), 'Update'),
            array('update', new I18n\Locale('de'), 'Aktualisieren')
        );
    }

    /**
     * @test
     * @dataProvider idAndLocaleForTranslation
     */
    public function simpleTranslationByIdWorks($id, $locale, $translation)
    {
        $result = $this->translator->translateById($id, array(), null, $locale, 'Main', 'TYPO3.Flow');
        $this->assertEquals($translation, $result);
    }

    /**
     * @return array
     */
    public function labelAndLocaleForTranslation()
    {
        return array(
            array('Update', new I18n\Locale('en'), 'Update'),
            array('Update', new I18n\Locale('de'), 'Aktualisieren')
        );
    }

    /**
     * @test
     * @dataProvider labelAndLocaleForTranslation
     */
    public function simpleTranslationByLabelWorks($label, $locale, $translation)
    {
        $result = $this->translator->translateByOriginalLabel($label, array(), null, $locale, 'Main', 'TYPO3.Flow');
        $this->assertEquals($translation, $result);
    }

    /**
     * @test
     */
    public function translationByIdReturnsNullOnFailure()
    {
        $result = $this->translator->translateById('non-existing-id');
        $this->assertNull($result);
    }
}
