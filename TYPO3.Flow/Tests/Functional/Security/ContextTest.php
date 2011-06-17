<?php
namespace TYPO3\FLOW3\Tests\Functional\Security;

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
 * Testcase for context
 *
 */
class ContextTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function afterSerializationAndUnserializationContextIsSetToUninitializedAgain() {
		$context = $this->objectManager->get('TYPO3\FLOW3\Security\Context');
		$this->assertFalse($context->isInitialized());

		$context->initialize();
		$this->assertTrue($context->isInitialized());

		$serializedContext = serialize($context);
		$unserializedContext = unserialize($serializedContext);

		$this->assertFalse($unserializedContext->isInitialized());
	}
}
?>