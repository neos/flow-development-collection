<?php
declare(strict_types=1);

namespace Neos\Flow\I18n\Cldr\Reader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\I18n\Cldr\CldrRepository;

/**
 * A reader for data placed in "currencyData" tag in CLDR.
 *
 * @Flow\Scope("singleton")
 */
class CurrencyReader
{
    /**
     * @var CldrRepository
     */
    protected $cldrRepository;

    /**
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * An array of fractions data, indexed by currency code.
     *
     * @var array
     */
    protected $fractions;

    /**
     * @param CldrRepository $repository
     * @return void
     */
    public function injectCldrRepository(CldrRepository $repository)
    {
        $this->cldrRepository = $repository;
    }

    /**
     * Injects the Flow_I18n_Cldr_Reader_CurrencyReader cache
     *
     * @param VariableFrontend $cache
     * @return void
     */
    public function injectCache(VariableFrontend $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Constructs the reader, loading parsed data from cache if available.
     *
     * @return void
     * @throws \Neos\Cache\Exception
     */
    public function initializeObject()
    {
        if ($this->cache->has('fractions')) {
            $this->fractions = $this->cache->get('fractions');
        } else {
            $this->generateFractions();
            $this->cache->set('fractions', $this->fractions);
        }
    }

    /**
     * Returns an array with keys "digits" and "rounding", each an integer.
     *
     * @param string $currencyCode
     * @return array ['digits' => int, 'rounding => int]
     */
    public function getFraction(string $currencyCode): array
    {
        if (array_key_exists($currencyCode, $this->fractions)) {
            return $this->fractions[$currencyCode];
        }

        return $this->fractions['DEFAULT'];
    }

    /**
     * Generates an internal representation of currency fractions which can be found
     * in supplementalData.xml CLDR file.
     *
     * @return void
     * @see CurrencyReader::$fractions
     */
    protected function generateFractions(): void
    {
        $model = $this->cldrRepository->getModel('supplemental/supplementalData');
        $currencyData = $model->getRawArray('currencyData');

        foreach ($currencyData['fractions'] as $fractionString) {
            $currencyCode = $model->getAttributeValue($fractionString, 'iso4217');
            $this->fractions[$currencyCode] = [
                'digits' => (int)$model->getAttributeValue($fractionString, 'digits'),
                'rounding' => (int)$model->getAttributeValue($fractionString, 'rounding')
            ];
        }
    }
}
