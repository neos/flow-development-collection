<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures;

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
 * A sample entity for tests
 *
 * @scope prototype
 * @entity
 */
class Post {

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Image
	 * @OneToOne
	 */
	protected $image;

	/**
	 * Yeah, only one comment allowed for a post ;-)
	 * But that's the easiest option for our functional test.
	 *
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Comment
	 * @OneToOne
	 */
	protected $comment;

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @param \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Image $image
	 */
	public function setImage($image) {
		$this->image = $image;
	}

	/**
	 * @return \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\Image
	 */
	public function getImage() {
		return $this->image;
	}

	/**
	 * @param $comment
	 * @return void
	 */
	public function setComment($comment)	{
		$this->comment = $comment;
	}

	/**
	 * @return Comment
	 */
	public function getComment()	{
		return $this->comment;
	}

}
?>