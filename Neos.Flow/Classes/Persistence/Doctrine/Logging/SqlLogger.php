<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Logging;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Logging\Middleware;
use Psr\Log\LoggerInterface;

/**
 * Just a stub placeholder so nothing has to be changed in user configuration,
 * This class has no meaning apart from being referenced in settings,
 * we extract the LoggerInterface from here and apply it to  a {@see Middleware}
 *
 * This class itself is mentioned in Settings to enable the logger,
 * the property is referenced in the Objects.yaml and if someone wanted to overwrite
 * the logger they would have done it there, so we cannot switch to constructor injection
 * without backwards incompatibility.
 */
class SqlLogger
{
    /**
     * @var LoggerInterface
     */
    public $logger;
}
