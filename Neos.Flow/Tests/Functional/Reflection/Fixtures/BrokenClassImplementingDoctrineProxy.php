<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures;

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
 * A fixture for testing that doctrine proxies can never be reflected.
 *
 * See issue http://forge.typo3.org/issues/29449 for details.
 */
class BrokenClassImplementingDoctrineProxy
{
    // This class just exists to satisfy the class loader -- else the compilation
    // run during functional tests will fail, as it expects to find a class named
    // like the file. It is NOT used for the testcase.
}


namespace Neos\Flow\Persistence\Doctrine\Proxies;

/**
 * This is our fake "doctrine proxy class"; which is in the same namespace
 * as all the other doctrine proxies. Trying to reflect this class should
 * result in an exception.
 *
 */
abstract class FakePackageDomainModelBrokenClassProxy implements \Doctrine\ORM\Proxy\Proxy
{
}
