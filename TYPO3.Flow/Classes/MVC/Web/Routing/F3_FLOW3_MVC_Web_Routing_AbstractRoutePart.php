<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web::Routing;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * abstract Route Part
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class AbstractRoutePart {

	/**
	 * Name of the Route Part
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Value of the Route Part after decoding.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Default value of the Route Part.
	 *
	 * @var mixed
	 */
	protected $defaultValue;

	/**
	 * Reference of the UriPatternSegmentCollection this Route Part belongs to.
	 *
	 * @var F3::FLOW3::MVC::Web::Routing::UriPatternSegmentCollection
	 */
	protected $uriPatternSegments;

	/**
	 * Returns name of the Route Part.
	 * 
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets name of the Route Part.
	 * 
	 * @param string $name
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setName($partName) {
		$this->name = $partName;
	}

	/**
	 * Returns value of the Route Part. Before match() is called this returns NULL.
	 * 
	 * @return mixed
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Sets default value of the Route Part.
	 * 
	 * @param mixed $defaultValue
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
	}

	/**
	 * Sets a reference to the UriPatternSegmentCollection this Route Part belongs to.
	 * 
	 * @param F3::FLOW3::MVC::Web::Routing::UriPatternSegmentCollection $uriPatternSegments
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUriPatternSegments(F3::FLOW3::MVC::Web::Routing::UriPatternSegmentCollection $uriPatternSegments) {
		$this->uriPatternSegments = $uriPatternSegments;
	}

	/**
	 * Returns the next Route Part instance in the current URI Pattern Segment Collection.
	 * 
	 * @return F3::FLOW3::MVC::Web::Routing::AbstractRoutePart the next Route Part or NULL if this is the last Route Part in the current collection.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getNextRoutePartInCurrentUriPatternSegment() {
		if ($this->uriPatternSegments === NULL) {
			return NULL;
		}
		return $this->uriPatternSegments->getNextRoutePartInCurrentUriPatternSegment();
	}

	/**
	 * Checks whether this Route Part corresponds to the given $uriSegments.
	 * This method does not only check if the Route Part matches. It can also
	 * shorten the $uriSegments-Array by one or more elements when matching is successful.
	 * This is why $uriSegments has to be passed by reference.
	 *
	 * @param array $uriSegments An array with one element per request URI segment.
	 * @return boolean TRUE if Route Part matched $uriSegments, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public abstract function match(array &$uriSegments);

	/**
	 * Checks whether this Route Part corresponds to the given $routeValues.
	 * This method does not only check if the Route Part matches. It also
	 * removes resolved elements from $routeValues-Array.
	 * This is why $routeValues has to be passed by reference.
	 *
	 * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
	 * @return boolean TRUE if Route Part can resolve one or more $routeValues elements, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public abstract function resolve(array &$routeValues);

}
?>