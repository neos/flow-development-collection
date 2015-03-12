<?php
namespace TYPO3\Flow\Tests\Unit\Object\Proxy;

/**
 * fixture "annotation" for the above test case
 */
class FooBarAnnotation {

	public $value;

	public function __construct($value = 1.2) {
		$this->value = $value;
	}
}
