<?php
namespace TYPO3\FLOW3\Mvc\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An ObjectPathMapping model
 * This contains the URI representation of an object (pathSegment)
 *
 * @FLOW3\Entity
 */
class ObjectPathMapping {

	/**
	 * Class name of the object this mapping belongs to
	 *
	 * @var string
	 * @ORM\Id
	 * @FLOW3\Validate(type="NotEmpty")
	 */
	protected $objectType;

	/**
	 * Pattern of the path segment (for example "{date}/{title}")
	 *
	 * @var string
	 * @ORM\Id
	 * @FLOW3\Validate(type="NotEmpty")
	 */
	protected $uriPattern;

	/**
	 * Path segment (URI representation) of the object this mapping belongs to
	 *
	 * @var string
	 * @ORM\Id
	 * @FLOW3\Validate(type="NotEmpty")
	 */
	protected $pathSegment;

	/**
	 * Identifier of the object this mapping belongs to
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * @param string $pathSegment
	 */
	public function setPathSegment($pathSegment) {
		$this->pathSegment = $pathSegment;
	}

	/**
	 * @return string
	 */
	public function getPathSegment() {
		return $this->pathSegment;
	}

	/**
	 * @param string $uriPattern
	 */
	public function setUriPattern($uriPattern) {
		$this->uriPattern = $uriPattern;
	}

	/**
	 * @return string
	 */
	public function getUriPattern() {
		return $this->uriPattern;
	}

	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param string $objectType
	 */
	public function setObjectType($objectType) {
		$this->objectType = $objectType;
	}

	/**
	 * @return string
	 */
	public function getObjectType() {
		return $this->objectType;
	}
}
?>
