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
 * The LocaleTree class representing a tree of locales. This structure contains
 * all locales available in current FLOW3 installation, and describes
 * hierarchical relations between them.
 *
 * Nodes in this tree are automatically sorted basing on contained locale. For
 * example, a node containing locale "en_GB" will be a child of a node with
 * locale "en".
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class LocaleTree implements \F3\FLOW3\Locale\LocaleTreeInterface {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Locale\LocaleNode
	 */
	protected $root;

	/**
	 * This is redundant structure which helps to parent locale without
	 * traversing by the tree.
	 *
	 * @var array of \F3\FLOW3\Locale\LocaleNode instances
	 */
	protected $nodesArray;

	/**
	 * Constructs an empty tree. Needs the objectManager to be injected before.
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function __construct(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->root = $this->objectManager->create('F3\FLOW3\Locale\LocaleNode');
	}

	/**
	 * A convenient method for adding a locale to the root of this tree.
	 *
	 * @see addLocaleInSubtree()
	 *
	 * @param \F3\FLOW3\Locale\Locale $value The Locale to be inserted
	 * @return boolean
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function addLocale(\F3\FLOW3\Locale\Locale $locale) {
		return $this->addLocaleInSubtree($locale, $this->root);
	}

	/**
	 * Adds locale to the tree, inserting it in position which retains sorting.
	 * Locales are sorted in such way that each subtree of root node contains
	 * locales wich are related to themselves, starting with the most generic
	 * locale node on top of subtree (eg "az"). Children of this node are more
	 * specific (eg "az_Cyryl" and "az_Latin"), which in turn can contain even
	 * more specific locales (accordingly "az_Cyryl_AZ" and "az_Latin_AZ").
	 *
	 * Note: it's not important which locales are on which "level" of the tree.
	 * For example, if there is no "en" locale available in FLOW3 installation,
	 * "en_GB" will be on the first level (direct child of root node), providing
	 * that this locale is available.
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale The Locale to be inserted
	 * @param \F3\FLOW3\Locale\LocaleNode $root Where to start searching
	 * @return boolean
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function addLocaleInSubtree(\F3\FLOW3\Locale\Locale $locale, \F3\FLOW3\Locale\LocaleNode $root) {
		foreach ($root->getChildren() as $child) {
			if ($child->containsLocale($locale)) {
				return FALSE;
			}

			if (strpos((string)$locale, (string)$child->getLocale()) === FALSE) {
				if (strpos((string)$child->getLocale(), (string)$locale) === FALSE) {
					continue;
				} else {
						// The new locale should be a parent of $child locale, as it's more generic
					$newNode = $this->objectManager->create('F3\FLOW3\Locale\LocaleNode', $locale);
					$this->nodesArray[(string)$locale] = $newNode;
					$child->insertAsParent($newNode);
					return TRUE;
				}
			} else {
					// The new locale is more specific than $child, it will be a descendant of $child
				return $this->addLocaleInSubtree($locale, $child);
			}
		}

			// No children of current $root node is any relative to the new Locale, add is as separate child
		$newNode = $this->objectManager->create('F3\FLOW3\Locale\LocaleNode', $locale);
		$this->nodesArray[(string)$locale] = $newNode;
		$root->addChild($newNode);
		return TRUE;
	}

	/**
	 * Returns a parent Locale object of the locale provided. The parent is
	 * a locale which is more generic than the one given as parameter. For
	 * example, the parent for locale en_GB will be locale en, of course if
	 * it exists in the locale tree of available locales.
	 *
	 * This method returns NULL when no parent locale is available, or when
	 * Locale object provided is not in three (ie it's not in a group of
	 * available locales).
	 *
	 * Note: to find a best-matching locale to one which doesn't exist in the
	 * system, please use findBestMatchingLocale() method from this class.
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale The Locale to search parent for
	 * @return mixed Existing \F3\FLOW3\Locale\Locale instance or NULL on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getParentLocaleOf($locale) {
		if (isset($this->nodesArray[(string)$locale]) === FALSE) {
			return NULL;
		}

		return $this->nodesArray[(string)$locale]->getParent()->getLocale();
	}

	/**
	 * A convenient method for searching a matching locale, starting from the
	 * root of this tree.
	 *
	 * @see findBestMatchingLocaleInSubtree()
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale The "template" Locale to be matched
	 * @return mixed Existing \F3\FLOW3\Locale\Locale instance on success, FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function findBestMatchingLocale(\F3\FLOW3\Locale\Locale $locale) {
		return $this->findBestMatchingLocaleInSubtree($locale, $this->root);
	}

	/**
	 * Returns Locale object which represents one of locales installed and which
	 * is most similar to the "template" Locale object given as parameter.
	 * Searching is done only in a subtree, where $root is a start node.
	 *
	 * @param \F3\FLOW3\Locale\Locale $locale The "template" Locale to be matched
	 * @param \F3\FLOW3\Locale\LocaleNode $root A node to start from
	 * @return mixed Existing \F3\FLOW3\Locale\Locale instance on success, FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function findBestMatchingLocaleInSubtree(\F3\FLOW3\Locale\Locale $locale, \F3\FLOW3\Locale\LocaleNode $root) {
		foreach ($root->getChildren() as $child) {
			if ($child->containsLocale($locale)) {
				return $child->getLocale();
			}

			if (strpos((string)$locale, (string)$child->getLocale()) === FALSE) {
				if (strpos((string)$child->getLocale(), (string)$locale) === FALSE) {
					continue;
				} else {
						// We have only more specific locale
					return NULL;
				}
			} else {
					// We have matching locale, let's check if any child of this locale matches better
				if (($betterMatch = $this->findBestMatchingLocaleInSubtree($locale, $child)) === NULL) {
					return $child->getLocale();
				} else {
					return $betterMatch;
				}
			}
		}

		return NULL;
	}
}
?>