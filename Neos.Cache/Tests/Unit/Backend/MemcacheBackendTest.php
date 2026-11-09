<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Backend;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;

include_once('MemcachedBackendTest.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
/**
 * Testcase for the cache to memcache backend
 */
#[RequiresPhpExtension('memcache')]
final class MemcacheBackendTest extends MemcachedBackendTest
{
}
