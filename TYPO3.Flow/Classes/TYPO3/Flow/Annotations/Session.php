<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Used to control the behavior of session handling when the annotated
 * method is called.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Session {

	/**
	 * Whether the annotated method triggers the start of a session.
	 * @var boolean
	 */
	public $autoStart = FALSE;

}

?>