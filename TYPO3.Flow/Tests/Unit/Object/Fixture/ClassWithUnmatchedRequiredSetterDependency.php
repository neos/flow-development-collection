<?php
namespace F3\FLOW3\Tests\Object\Fixture;

class ClassWithUnmatchedRequiredSetterDependency {

	public $requiredSetterArgument;

	public function injectRequiredSetterArgument(\stdClass $setterArgument) {
		$this->requiredSetterArgument = $setterArgument;
	}
}
?>