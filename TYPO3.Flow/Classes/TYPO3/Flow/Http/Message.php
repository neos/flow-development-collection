<?php
namespace TYPO3\Flow\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * @deprecated since 3.0 Only present for backward compatibility, instantiate your own Message implementation which inherits from AbstractMessage, or use the Request or Response classes.
 * @Flow\Proxy(false)
 */
class Message extends AbstractMessage {

	/**
	 * Method stub to satisfy the prescribed presence in AbstractMessage
	 * @see \TYPO3\Flow\Http\AbstractMessage::getStartLine
	 */
	public function getStartLine() {
	}
}
