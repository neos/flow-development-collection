<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

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
 */

/**
 * Testcase for the LocaleNode class
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class LocaleNodeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var array An array of \F3\FLOW3\Locale\Locale instances
	 */
	protected $locales;

	/**
	 * @var array An array of \F3\FLOW3\Locale\LocaleNode instances
	 */
	protected $nodes;

	/**
	 * @var \F3\FLOW3\Locale\LocaleNode
	 */
	protected $root;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		$this->locales = array(
			new \F3\FLOW3\Locale\Locale('en'),
			new \F3\FLOW3\Locale\Locale('pl_PL'),
			new \F3\FLOW3\Locale\Locale('de'),
		);

		$this->nodes = array();
		foreach ($this->locales as $locale) {
			$this->nodes[] = new \F3\FLOW3\Locale\LocaleNode($locale);
		}

		$this->root = new \F3\FLOW3\Locale\LocaleNode();
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function addChildWorksForManyChildren() {
		foreach ($this->nodes as $node) {
			$nodeAttached = $this->root->addChild($node);
			$this->assertEquals(TRUE, $nodeAttached);
		}

		$nodeAttachedAgain = $this->root->addChild(end($this->nodes));
		$this->assertEquals(FALSE, $nodeAttachedAgain);

		$returnedNodes = array();
		$returnedNodes[0] = $this->root->getFirstChild();
		$returnedNodes[1] = $returnedNodes[0]->getNextSibling();
		$returnedNodes[2] = $returnedNodes[1]->getNextSibling();

		foreach ($returnedNodes as $index => $returnedNode) {
			$this->assertSame($this->locales[$index], $returnedNode->getValue());
		}
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getChildWorksForManyChildren() {
		foreach ($this->nodes as $node) {
			$this->root->addChild($node);
		}
		
		$index = 0;
		foreach ($this->root->getChildren() as $child) {
			$this->assertSame($this->nodes[$index], $child);
			++$index;
		}

		$this->assertSame(count($this->locales), count($this->root->getChildren()));
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function findChildByValueWorksForManyChildren() {
		foreach ($this->nodes as $node) {
			$this->root->addChild($node);
		}

		$foundNode = $this->root->findChildByValue($this->locales[2]);
		$this->assertNotEquals(FALSE, $foundNode);
		$this->assertSame($this->locales[2], $foundNode->getValue());

		$nonExistingNode = $this->root->findChildByValue(new \F3\FLOW3\Locale\Locale('sv'));
		$this->assertEquals(FALSE, $nonExistingNode);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function childExistsWorks() {
		foreach ($this->nodes as $node) {
			$this->root->addChild($node);
		}

		$childExists = $this->root->childExists($this->nodes[2]);
		$this->assertEquals(TRUE, $childExists);

		$childExists = $this->root->childExists(new \F3\FLOW3\Locale\LocaleNode());
		$this->assertEquals(FALSE, $childExists);
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function becomeChildOfWorks() {
		foreach ($this->nodes as $node) {
			$this->root->addChild($node);
		}

		$newNode = new \F3\FLOW3\Locale\LocaleNode(new \F3\FLOW3\Locale\Locale('pl'));
		$oldNode = $this->root->findChildByValue($this->locales[1]);

		$oldNode->becomeChildOf($newNode);

		$this->assertSame($newNode, $oldNode->getParent());
		$this->assertSame($newNode->getNextSibling(), $this->nodes[2]);
		$this->assertSame($newNode->getParent(), $this->root);
	}
}
?>