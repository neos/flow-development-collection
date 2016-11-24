<?php
namespace Neos\Flow\I18n;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A Configuration instance represents settings to be used with the I18n
 * functionality. Examples of such settings are the locale to be used and
 * overrides for message catalogs.
 */
class Configuration
{
    /**
     * @var Locale
     */
    protected $defaultLocale;

    /**
     * @var Locale
     */
    protected $currentLocale;

    /**
     * @var array
     */
    protected $fallbackRule = ['strict' => false, 'order' => []];

    /**
     * Constructs a new configuration object with the given locale identifier to
     * be used as the default locale of this configuration.
     *
     * @param string $defaultLocaleIdentifier
     * @throws Exception\InvalidLocaleIdentifierException
     */
    public function __construct($defaultLocaleIdentifier)
    {
        try {
            $this->defaultLocale = new Locale($defaultLocaleIdentifier);
        } catch (Exception\InvalidLocaleIdentifierException $exception) {
            throw new Exception\InvalidLocaleIdentifierException('The default locale identifier "' . $defaultLocaleIdentifier . '" given is invalid.', 1280935191);
        }
    }

    /**
     * Returns the default locale of this configuration.
     *
     * @return Locale
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Sets the current locale of this configuration.
     *
     * @param Locale $locale
     * @return void
     */
    public function setCurrentLocale(Locale $locale)
    {
        $this->currentLocale = $locale;
    }

    /**
     * Returns the current locale. This is the default locale if
     * no current locale has been set or the set current locale has
     * a language code of "mul".
     *
     * @return Locale
     */
    public function getCurrentLocale()
    {
        if (!$this->currentLocale instanceof Locale
            || $this->currentLocale->getLanguage() === 'mul') {
            return $this->defaultLocale;
        }
        return $this->currentLocale;
    }

    /**
     * Allows to set a fallback order for locale resolving. If not set,
     * the implicit inheritance of locales will be used. That is, if a
     * locale of en_UK is requested, matches will be searched for in en_UK
     * and en before trying the default locale configured in Flow.
     *
     * If this is given an order of [dk, za, fr_CA] a request for en_UK will
     * be looked up in en_UK, en, dk, za, fr_CA, fr before trying the default
     * locale.
     *
     * If strict flag is given in the array, the above example would instead look
     * in en_UK, dk, za, fr_CA before trying the default locale. In other words,
     * the implicit fallback is not applied to the locales in the fallback rule.
     *
     * Here is an example:
     *   array('strict' => FALSE, 'order' => array('dk', 'za'))
     *
     * @param array $fallbackRule
     */
    public function setFallbackRule(array $fallbackRule)
    {
        if (!array_key_exists('order', $fallbackRule)) {
            throw new \InvalidArgumentException('The given fallback rule did not contain an order element.', 1406710671);
        }
        if (!array_key_exists('strict', $fallbackRule)) {
            $fallbackRule['strict'] = false;
        }
        $this->fallbackRule = $fallbackRule;
    }

    /**
     * Returns the current fallback rule.
     *
     * @return array
     * @see setFallbackRule()
     */
    public function getFallbackRule()
    {
        return $this->fallbackRule;
    }
}
