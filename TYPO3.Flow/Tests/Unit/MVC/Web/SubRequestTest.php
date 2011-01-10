<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\MVC\Web;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Web SubRequest class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SubRequestTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsParentRequest() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$subRequest = new \F3\FLOW3\MVC\Web\SubRequest($mockRequest);
		$this->assertSame($mockRequest, $subRequest->getParentRequest());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function argumentNamespaceDefaultsToAnEmptyString() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$subRequest = new \F3\FLOW3\MVC\Web\SubRequest($mockRequest);
		$this->assertSame('', $subRequest->getArgumentNamespace());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function argumentNamespaceCanBeSpecified() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$subRequest = new \F3\FLOW3\MVC\Web\SubRequest($mockRequest);
		$subRequest->setArgumentNamespace('someArgumentNamespace');
		$this->assertSame('someArgumentNamespace', $subRequest->getArgumentNamespace());
	}
}
?>