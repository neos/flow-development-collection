<?php

declare(strict_types=1);

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
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the I18N translations
 *
 */
final class TranslatorTest extends FunctionalTestCase
{
    protected Translator $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->objectManager->get(Translator::class);
    }

    public static function idAndLocaleForTranslation(): \Iterator
    {
        yield ['authentication.username', new Locale('en'), 'Username'];
        yield ['authentication.username', new Locale('de_CH'), 'Benutzername'];
        yield ['update', new Locale('en'), 'Update'];
        yield ['update', new Locale('de'), 'Aktualisieren'];
    }

    #[DataProvider('idAndLocaleForTranslation')]
    #[Test]
    public function simpleTranslationByIdWorks($id, $locale, $translation): void
    {
        $result = $this->translator->translateById($id, [], null, $locale, 'Main', 'Neos.Flow');
        self::assertEquals($translation, $result);
    }

    public static function labelAndLocaleForTranslation(): \Iterator
    {
        yield ['Update', new Locale('en'), 'Update'];
        yield ['Update', new Locale('de'), 'Aktualisieren'];
    }

    #[DataProvider('labelAndLocaleForTranslation')]
    #[Test]
    public function simpleTranslationByLabelWorks($label, $locale, $translation): void
    {
        $result = $this->translator->translateByOriginalLabel($label, [], null, $locale, 'Main', 'Neos.Flow');
        self::assertEquals($translation, $result);
    }

    public static function labelAndArgumentsForTranslation(): \Iterator
    {
        yield ['The given value is expected to be {0}.', ['foo'], 'The given value is expected to be foo.'];
        yield ['Untranslated label value is expected to be {0}.', ['foo'], 'Untranslated label value is expected to be foo.'];
    }

    #[DataProvider('labelAndArgumentsForTranslation')]
    #[Test]
    public function translationByLabelUsesPlaceholders($label, $arguments, $translation): void
    {
        $result = $this->translator->translateByOriginalLabel($label, $arguments, null, new Locale('en'), 'ValidationErrors', 'Neos.Flow');
        self::assertEquals($translation, $result);
    }

    #[Test]
    public function translationByIdReturnsNullOnFailure(): void
    {
        $result = $this->translator->translateById('non-existing-id');
        self::assertNull($result);
    }
}
