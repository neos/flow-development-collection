<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use Doctrine\ORM\Mapping as ORM;

/**
 * A sample entity for tests
 *
 * @FLOW3\Entity
 */
class Image {

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $data;

	/**
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param string $data
	 * @return void
	 */
	public function setData($data) {
		$this->data = $data;
	}

}
?>