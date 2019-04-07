<?php

namespace Neos\Flow\Security\Authentication;

class CredentialsSource {

	/**
	 * @var string
	 */
	protected $credentialsSource;

	/**
	 * @param string $credentialsSource
	 */
	public function __construct(string $credentialsSource)
	{
		$this->credentialsSource = $credentialsSource;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->credentialsSource;
	}
}