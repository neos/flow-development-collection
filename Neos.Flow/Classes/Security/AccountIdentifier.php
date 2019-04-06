<?php

namespace Neos\Flow\Security;

class AccountIdentifier {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @param string $identifier
	 */
	public function __construct(string $identifier)
	{
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}
}