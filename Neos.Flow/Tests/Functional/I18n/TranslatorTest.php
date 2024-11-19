<?php
namespace Neos\Flow\Tests\Functional\I18n;

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
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the I18N translations
 *
 */
class TranslatorTest extends FunctionalTestCase
{
    protected I18n\Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->objectManager->get(I18n\Translator::class);
    }

    public static function idAndLocaleForTranslation(): array
    {
        return [
            ['authentication.username', new I18n\Locale('en'), 'Username'],
            ['authentication.username', new I18n\Locale('de_CH'), 'Benutzername'],
            ['update', new I18n\Locale('en'), 'Update'],
            ['update', new I18n\Locale('de'), 'Aktualisieren']
        ];
    }

    /**
     * @test
     * @dataProvider idAndLocaleForTranslation
     */
    public function simpleTranslationByIdWorks($id, $locale, $translation): void
    {
        $result = $this->translator->translateById($id, [], null, $locale, 'Main', 'Neos.Flow');
        self::assertEquals($translation, $result);
    }

    public static function labelAndLocaleForTranslation(): array
    {
        return [
            ['Update', new I18n\Locale('en'), 'Update'],
            ['Update', new I18n\Locale('de'), 'Aktualisieren']
        ];
    }

    /**
     * @test
     * @dataProvider labelAndLocaleForTranslation
     */
    public function simpleTranslationByLabelWorks($label, $locale, $translation): void
    {
        $result = $this->translator->translateByOriginalLabel($label, [], null, $locale, 'Main', 'Neos.Flow');
        self::assertEquals($translation, $result);
    }

    public static function labelAndArgumentsForTranslation(): array
    {
        return [
            ['The given value is expected to be {0}.', ['foo'], 'The given value is expected to be foo.'],
            ['Untranslated label value is expected to be {0}.', ['foo'], 'Untranslated label value is expected to be foo.']
        ];
    }

    /**
     * @test
     * @dataProvider labelAndArgumentsForTranslation
     */
    public function translationByLabelUsesPlaceholders($label, $arguments, $translation): void
    {
        $result = $this->translator->translateByOriginalLabel($label, $arguments, null, new I18n\Locale('en'), 'ValidationErrors', 'Neos.Flow');
        self::assertEquals($translation, $result);
    }

    /**
     * @test
     */
    public function translationByIdReturnsNullOnFailure(): void
    {
        $result = $this->translator->translateById('non-existing-id');
        self::assertNull($result);
    }
}
