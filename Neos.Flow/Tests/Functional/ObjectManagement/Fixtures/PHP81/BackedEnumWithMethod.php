<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81;

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
 * A PHP 8.1 value-backed enum with methods
 */
enum BackedEnumWithMethod: string
{
    case ESPRESSO = 'esp';
    case RISTRETTO = 'ris';
    case FLAT_WHITE = 'flw';

    public function label(): string
    {
        return BackedEnumWithMethod::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::ESPRESSO => 'Espresso',
            self::RISTRETTO => 'Ristretto',
            self::FLAT_WHITE => 'Flat White'
        };
    }
}
