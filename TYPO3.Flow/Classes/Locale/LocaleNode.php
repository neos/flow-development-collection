<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

/* *
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
 * The LocaleNode class representing a node in a tree of locales. Instances of
 * this class are used to form a hierarchical relation between locales available
 * in FLOW3 installation.
 *
 * This is an implementation of simple First-Child/Next-Sibling tree.
 *
 * Note: this implementation is not complete, eg there is no method for children
 * removal. As for now, this is not required for Locale subpackage needs.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class LocaleNode {

	/**
	 * First of this node's children. Other children are attached in chain to
	 * each other by $nextSibling property.
	 *
	 * @var \F3\FLOW3\Locale\LocaleNode
	 */
	protected $firstChild = NULL;

	/**
	 * This node's direct sibling. Other siblings are attached in chain to each
	 * other.
	 *
	 * @var \F3\FLOW3\Locale\LocaleNode
	 */
	protected $nextSibling = NULL;

	/**
	 * A node just before this node ("on the left").
	 *
	 * @var \F3\FLOW3\Locale\LocaleNode
	 */
	protected $previousSibling = NULL;

	/**
	 * This node's parent.
	 *
	 * @var \F3\FLOW3\Locale\LocaleNode
	 */
	protected $parent = NULL;

	/**
	 * Value stored in this node - Locale object.
	 *
	 * @var \F3\FLOW3\Locale\Locale
	 */
	protected $value = NULL;

	/**
	 * Constructs the LocaleNode.
	 *
	 * @param \F3\FLOW3\Locale\Locale $value A Locale object to be contained in this node
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function __construct(\F3\FLOW3\Locale\Locale $value = NULL) {
		$this->value = $value;
	}

	/**
	 * Attaches given node as a child of this node, so the node provided will be
	 * a direct (first) child, or will be added as a sibling of one of this nodes
	 * child's siblings :-).
	 *
	 * This methods checks nodes for identity, so no infinite loops will occur.
	 *
	 * @param \F3\FLOW3\Locale\LocaleNode $node The node to be attached as child
	 * @return boolean
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function addChild(\F3\FLOW3\Locale\LocaleNode $node) {
		if ($this->firstChild === NULL) {
			$this->firstChild = $node;
			$node->parent = $this;
			return TRUE;
		}

		return $this->firstChild->addSibling($node);
	}

	/**
	 * Convenient method to get all children of this node in array.
	 *
	 * @return array Array of existing \F3\FLOW3\Locale\LocaleNode instances or an empty array on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getChildren() {
		$children = array();

		if ($this->firstChild !== NULL) {
			$children[] = $this->firstChild;
			$children = array_merge($children, $this->firstChild->getSiblings());
		}

		return $children;
	}

	/**
	 * Searches for a node within group of children of this node, which contains
	 * a Locale objects which is equal to the one given as parameter.
	 *
	 * @param \F3\FLOW3\Locale\Locale $value The value of node's to be found
	 * @return mixed Existing \F3\FLOW3\Locale\LocaleNode instance on success, FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function findChildByValue(\F3\FLOW3\Locale\Locale $value) {
		foreach ($this->getChildren() as $child) {
			if ($child->hasEqualsValue($value) === TRUE) {
				return $child;
			}
		}

		return FALSE;
	}

	/**
	 * Checks if node given already is one of this node's children.
	 *
	 * Note: this method checks for identity, please use findChildByValue() for
	 * equality check.
	 *
	 * @param \F3\FLOW3\Locale\LocaleNode $node The node to compare
	 * @return boolean
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function childExists(\F3\FLOW3\Locale\LocaleNode $node) {
		if ($this->firstChild === NULL) {
			return FALSE;
		}

		if ($this->firstChild === $node) {
			return TRUE;
		}

		return $this->firstChild->siblingExists($node);
	}

	/**
	 * Attaches given node as a sibling of this node, so the node provided will
	 * be attached as nextSibling of this node, or one of this node's siblings.
	 *
	 * This methods checks nodes for identity, so no infinite loops will occur.
	 *
	 * @param \F3\FLOW3\Locale\LocaleNode $node The node to be attached as child
	 * @return boolean
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function addSibling(\F3\FLOW3\Locale\LocaleNode $node) {
		if ($this->parent === NULL) {
			throw new \F3\FLOW3\Locale\Exception\InvalidStateException('Root element cannot have any siblings.', 1273939983);
		}

		if ($this->nextSibling === NULL) {
			$this->nextSibling = $node;
			$node->previousSibling = $this;
			$node->parent = $this->parent;
			return TRUE;
		}

		if ($this->siblingExists($node) === TRUE) {
			return FALSE;
		}

		return $this->nextSibling->addSibling($node);
	}

	/**
	 * Convenient method to get all siblings of this node in an array.
	 *
	 * @return array Array of existing \F3\FLOW3\Locale\LocaleNode instances or an empty array on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getSiblings() {
		$siblings = array();

		$sibling = $this;
		while (($sibling = $sibling->nextSibling) !== NULL) {
			$siblings[] = $sibling;
		}

		return $siblings;
	}

	/**
	 * Checks if node given already is one of this node's siblings.
	 *
	 * Note: this method checks for identity, please use findSiblingByValue() for
	 * equality check.
	 *
	 * @param \F3\FLOW3\Locale\LocaleNode $node The node to compare
	 * @return boolean
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function siblingExists(\F3\FLOW3\Locale\LocaleNode $node) {
		if ($this->nextSibling === NULL) {
			return FALSE;
		}

		if ($this->nextSibling === $node) {
			return TRUE;
		}

		return $this->nextSibling->siblingExists($node);
	}

	/**
	 * Inserts given node in a place of $this node, which in turn becomes a
	 * child of newly inserted node. The subtree structure "under" $this node is
	 * preserved, but nextSibling of $this node becomes a sibling of newly
	 * inserted node.
	 *
	 * @param \F3\FLOW3\Locale\LocaleNode $node The node to insert in place of this (current) node
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function becomeChildOf(\F3\FLOW3\Locale\LocaleNode $node) {
		if ($this->parent !== NULL && $this->parent->firstChild === $this) {
			$this->parent->firstChild = $node;
		}

		if ($this->previousSibling !== NULL) {
			$this->previousSibling->nextSibling = $node;
			$this->previousSibling = NULL;
		}

		$node->parent = $this->parent;
		$node->nextSibling = $this->nextSibling;
		$node->firstChild = $this;
		$this->parent = $node;
		$this->nextSibling = NULL;
	}

	/**
	 * Convenient method to comparing whole Locale object contained in this node
	 * with provided Locale object. It works different from equals operator (==)
	 * as each Locale part is compared strictly.
	 *
	 * @param \F3\FLOW3\Locale\Locale $value The Locale object to compare with
	 * @return boolean
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function hasEqualsValue(\F3\FLOW3\Locale\Locale $value) {
		if ($this->value->getLanguage() === $value->getLanguage()) {
			if ($this->value->getScript() === $value->getScript()) {
				if ($this->value->getRegion() === $value->getRegion()) {
					if ($this->value->getVariant() === $value->getVariant()) {
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}


	/**
	 * Returns the first value stored in this node.
	 *
	 * @return mixed Existing \F3\FLOW3\Locale\Locale instance or NULL
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the first (direct) child of this node.
	 *
	 * @return mixed Existing \F3\FLOW3\Locale\LocaleNode instance or NULL
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getFirstChild() {
		return $this->firstChild;
	}

	/**
	 * Returns the next (direct) sibling of this node.
	 *
	 * @return mixed Existing \F3\FLOW3\Locale\LocaleNode instance or NULL
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getNextSibling() {
		return $this->nextSibling;
	}

	/**
	 * Returns the parent of this node.
	 *
	 * @return mixed Existing \F3\FLOW3\Locale\LocaleNode instance or NULL
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getParent() {
		return $this->parent;
	}
}
?>
