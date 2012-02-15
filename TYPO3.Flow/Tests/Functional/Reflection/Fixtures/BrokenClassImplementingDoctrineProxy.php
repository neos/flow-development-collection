<?php
namespace TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * A fixture for testing that doctrine proxies can never be reflected.
 *
 * See issue http://forge.typo3.org/issues/29449 for details.
 */
class BrokenClassImplementingDoctrineProxy {
	// This class just exists to satisfy the class loader -- else the compilation
	// run during functional tests will fail, as it expects to find a class named
	// like the file. It is NOT used for the testcase.
}


namespace TYPO3\FLOW3\Persistence\Doctrine\Proxies;

/**
 * This is our fake "doctrine proxy class"; which is in the same namespace
 * as all the other doctrine proxies. Trying to reflect this class should
 * result in an exception.
 *
 */
abstract class FakePackageDomainModelBrokenClassProxy implements \Doctrine\ORM\Proxy\Proxy {

}
?>