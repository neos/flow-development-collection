<?php
namespace TYPO3\Flow\Security\Authorization;

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
 * This class represents a concrete vote, returned from privileges and used to
 * evaluate privilege decisions in the privilege manager.
 */
class PrivilegeVoteResult {

	const VOTE_GRANT = 'GRANT';
	const VOTE_ABSTAIN = 'ABSTAIN';
	const VOTE_DENY = 'DENY';

	/**
	 * @var string one of the VOTE_* constants
	 */
	protected $vote;

	/**
	 * @var string
	 */
	protected $reason;

	/**
	 * @param string $vote one of the VOTE_* constants
	 * @param string $reason (optional) description of why this vote granted/denied/abstained
	 */
	function __construct($vote, $reason = NULL) {
		$this->vote = $vote;
		$this->reason = $reason;
	}

	/**
	 * @return string
	 */
	public function getVote() {
		return $this->vote;
	}

	/**
	 * @return boolean
	 */
	public function isGranted() {
		return $this->vote === self::VOTE_GRANT;
	}

	/**
	 * @return boolean
	 */
	public function isAbstained() {
		return $this->vote === self::VOTE_ABSTAIN;
	}

	/**
	 * @return boolean
	 */
	public function isDenied() {
		return $this->vote === self::VOTE_DENY;
	}

	/**
	 * Returns a message with information about the reasons for this vote.
	 *
	 * @return string
	 */
	public function getReason() {
		return $this->reason;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->vote . ' Reason: ' . $this->reason;
	}
}
