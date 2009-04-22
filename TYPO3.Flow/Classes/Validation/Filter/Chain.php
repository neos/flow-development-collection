<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Filter;

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
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 */

/**
 * A filter to chain many filters
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Chain implements \F3\FLOW3\Validation\FilterInterface {

	/**
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Returns the filtered subject.
	 *
	 * @param object The subject that should be filtered
	 */
	public function filter($subject) {
		foreach ($this->filters as $filter) {
			$filter->filter($subject);
		}
	}

	/**
	 * Adds a new filter to the chain. Returns the index of the chain entry.
	 *
	 * @param \F3\FLOW3\Validation\FilterInterface The filter that should be added
	 * @return integer The index of the new chain entry
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addFilter(\F3\FLOW3\Validation\FilterInterface $filter) {
		$this->filters[] = $filter;
		return count($this->filters) - 1;
	}

	/**
	 * Returns the filter with the given index of the chain.
	 *
	 * @param  integer The index of the filter that should be returned
	 * @return \F3\FLOW3\Validation\FilterInterface The requested filter
	 * @throws \F3\FLOW3\Validation\Exception\InvalidChainIndex
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getFilter($index) {
		if (!isset($this->filters[$index])) throw new \F3\FLOW3\Validation\Exception\InvalidChainIndex('Invalid chain index.', 1207215864);
		return $this->filters[$index];
	}

	/**
	 * Removes the filters with the given index of the chain.
	 *
	 * @param integer The index of the filter that should be removed
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeFilter($index) {
		if (!isset($this->filters[$index])) throw new \F3\FLOW3\Validation\Exception\InvalidChainIndex('Invalid chain index.', 1207020177);
		unset($this->filters[$index]);
	}
}

?>