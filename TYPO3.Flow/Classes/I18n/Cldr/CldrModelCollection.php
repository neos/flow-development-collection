<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\I18n\Cldr;

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
 * A class providing data from many CLDR files having hierarchical relation
 * between themselves.
 *
 * As for now, implementation is very simple. Results from each CldrModel
 * controlled by this class are merged ad hoc and returned.
 *
 * The 'alias' tags from CLDR are not handled correctly yet. They are supported
 * by CldrModel, but specification says that hierarchy should be taken into
 * account during alias resolution, however nodes are searched only within one
 * file (represented by CldrModel) for now.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class CldrModelCollection {

	/**
	 * A collection of models in hierarchy.
	 *
	 * @var array<\F3\FLOW3\I18n\Cldr\CldrModel>
	 */
	protected $models;

	/**
	 * Constructs the model.
	 *
	 * An array of CldrModel instances is required. They have to be sorted
	 * with hierarchy in mind - the higher index in array, the more general
	 * file is (i.e. root should be on last index).
	 *
	 * @param array<\F3\FLOW3\I18n\Cldr\CldrModel> $models An array of CldrModel instances
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function __construct(array $models) {
		$this->models = $models;
	}

	/**
	 * Returns multi-dimensional array representing desired node and it's children.
	 *
	 * @param string $path A path to the node to get
	 * @return mixed Array of matching data, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getRawArray($path) {
		$data = array();
		foreach ($this->models as $model) {
			$parsedNodes = $model->getRawArray($path);
			if ($parsedNodes !== FALSE && is_array($parsedNodes)) {
				$data = array_merge($data, $parsedNodes);
			} elseif ($parsedNodes !== FALSE) {
					$data = $parsedNodes;
			}
		}

		if (!empty($data)) {
			return $data;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns string element from a path given.
	 *
	 * @param string $path A path to the element to get
	 * @return mixed String with desired element, or FALSE on failure
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function getElement($path) {
		$data = $this->getRawArray($path);

		if (is_array($data)) {
			if (isset($data[\F3\FLOW3\I18n\Cldr\CldrParser::NODE_WITHOUT_ATTRIBUTES])) {
				return $data[\F3\FLOW3\I18n\Cldr\CldrParser::NODE_WITHOUT_ATTRIBUTES];
			} else {
				return FALSE;
			}
		} else {
			return $data;
		}
	}
}

?>