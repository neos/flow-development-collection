<?php

namespace Neos\Flow\Security\Authentication;

class AuthenticationProviderName {

	/**
	 * @var string
	 */
	protected $authenticationProviderName;

	/**
	 * @param string $authenticationProviderName
	 */
	public function __construct(string $authenticationProviderName)
	{
		$this->authenticationProviderName = $authenticationProviderName;
	}

	/**
	 * @return string
	 */
	public function getAuthenticationProviderName()
	{
		return $this->authenticationProviderName;
	}

}