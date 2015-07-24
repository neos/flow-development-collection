<?php
namespace TYPO3\Flow\Cli;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * Represents a command
 */
class Command {

	/**
	 * @var string
	 */
	protected $controllerClassName;

	/**
	 * @var string
	 */
	protected $controllerCommandName;

	/**
	 * @var string
	 */
	protected $commandIdentifier;

	/**
	 * @var \TYPO3\Flow\Reflection\MethodReflection
	 */
	protected $commandMethodReflection;

	/**
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructor
	 *
	 * @param string $controllerClassName Class name of the controller providing the command
	 * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
	 * @throws \InvalidArgumentException
	 */
	public function __construct($controllerClassName, $controllerCommandName) {
		$this->controllerClassName = $controllerClassName;
		$this->controllerCommandName = $controllerCommandName;

		$matchCount = preg_match('/^(?P<PackageNamespace>\w+(?:\\\\\w+)*)\\\\Command\\\\(?P<ControllerName>\w+)CommandController$/', $controllerClassName, $matches);
		if ($matchCount !== 1) {
			throw new \InvalidArgumentException('Invalid controller class name "' . $controllerClassName . '". Make sure your controller is in a folder named "Command" and it\'s name ends in "CommandController"', 1305100019);
		}

		$this->commandIdentifier = strtolower(str_replace('\\', '.', $matches['PackageNamespace']) . ':' . $matches['ControllerName'] . ':' . $controllerCommandName);
	}

	/**
	 * @param ReflectionService $reflectionService Reflection service
	 */
	public function injectReflectionService(ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param ObjectManagerInterface $objectManager
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @return string
	 */
	public function getControllerClassName() {
		return $this->controllerClassName;
	}

	/**
	 * @return string
	 */
	public function getControllerCommandName() {
		return $this->controllerCommandName;
	}

	/**
	 * Returns the command identifier for this command
	 *
	 * @return string The command identifier for this command, following the pattern packagekey:controllername:commandname
	 */
	public function getCommandIdentifier() {
		return $this->commandIdentifier;
	}

	/**
	 * Returns a short description of this command
	 *
	 * @return string A short description
	 */
	public function getShortDescription() {
		$commandMethodReflection = $this->getCommandMethodReflection();
		$lines = explode(chr(10), $commandMethodReflection->getDescription());
		$shortDescription = ((count($lines) > 0) ? trim($lines[0]) : '<no description available>') . ($this->isDeprecated() ? ' <b>(DEPRECATED)</b>' : '');

		if ($commandMethodReflection->getDeclaringClass()->implementsInterface('TYPO3\Flow\Cli\DescriptionAwareCommandControllerInterface')) {
			$shortDescription = call_user_func(array($this->controllerClassName, 'processDescription'), $this->controllerCommandName, $shortDescription, $this->objectManager);
		}
		return $shortDescription;
	}

	/**
	 * Returns a longer description of this command
	 * This is the complete method description except for the first line which can be retrieved via getShortDescription()
	 * If The command description only consists of one line, an empty string is returned
	 *
	 * @return string A longer description of this command
	 */
	public function getDescription() {
		$commandMethodReflection = $this->getCommandMethodReflection();
		$lines = explode(chr(10), $commandMethodReflection->getDescription());
		array_shift($lines);
		$descriptionLines = array();
		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			if ($descriptionLines !== array() || $trimmedLine !== '') {
				$descriptionLines[] = $trimmedLine;
			}
		}
		$description = implode(chr(10), $descriptionLines);
		if ($commandMethodReflection->getDeclaringClass()->implementsInterface('TYPO3\Flow\Cli\DescriptionAwareCommandControllerInterface')) {
			$description = call_user_func(array($this->controllerClassName, 'processDescription'), $this->controllerCommandName, $description, $this->objectManager);
		}
		return $description;
	}

	/**
	 * Returns TRUE if this command expects required and/or optional arguments, otherwise FALSE
	 *
	 * @return boolean
	 */
	public function hasArguments() {
		return count($this->getCommandMethodReflection()->getParameters()) > 0;
	}

	/**
	 * Returns an array of \TYPO3\Flow\Cli\CommandArgumentDefinition that contains
	 * information about required/optional arguments of this command.
	 * If the command does not expect any arguments, an empty array is returned
	 *
	 * @return array<\TYPO3\Flow\Cli\CommandArgumentDefinition>
	 */
	public function getArgumentDefinitions() {
		if (!$this->hasArguments()) {
			return array();
		}
		$commandArgumentDefinitions = array();
		$commandMethodReflection = $this->getCommandMethodReflection();
		$annotations = $commandMethodReflection->getTagsValues();
		$commandParameters = $this->reflectionService->getMethodParameters($this->controllerClassName, $this->controllerCommandName . 'Command');
		$i = 0;
		foreach ($commandParameters as $commandParameterName => $commandParameterDefinition) {
			$explodedAnnotation = explode(' ', $annotations['param'][$i]);
			array_shift($explodedAnnotation);
			array_shift($explodedAnnotation);
			$description = implode(' ', $explodedAnnotation);
			$required = $commandParameterDefinition['optional'] !== TRUE;
			$commandArgumentDefinitions[] = new CommandArgumentDefinition($commandParameterName, $required, $description);
			$i ++;
		}
		return $commandArgumentDefinitions;
	}

	/**
	 * Tells if this command is internal and thus should not be exposed through help texts, user documentation etc.
	 * Internal commands are still accessible through the regular command line interface, but should not be used
	 * by users.
	 *
	 * @return boolean
	 */
	public function isInternal() {
		return $this->getCommandMethodReflection()->isTaggedWith('internal');
	}

	/**
	 * Tells if this command is deprecated and thus should be marked as such in help texts, user documentation etc.
	 * Deprecated commands are still accessible through the regular command line interface, but should not be used
	 * by users anymore.
	 *
	 * @return boolean
	 */
	public function isDeprecated() {
		return $this->getCommandMethodReflection()->isTaggedWith('deprecated');
	}

	/**
	 * Tells if this command flushes all caches and thus needs special attention in the interactive shell.
	 *
	 * Note that neither this method nor the @Flow\FlushesCaches annotation is currently part of the official API.
	 *
	 * @return boolean
	 */
	public function isFlushingCaches() {
		return $this->getCommandMethodReflection()->isTaggedWith('flushescaches');
	}

	/**
	 * Returns an array of command identifiers which were specified in the "@see"
	 * annotation of a command method.
	 *
	 * @return array
	 */
	public function getRelatedCommandIdentifiers() {
		$commandMethodReflection = $this->getCommandMethodReflection();
		if (!$commandMethodReflection->isTaggedWith('see')) {
			return array();
		}

		$relatedCommandIdentifiers = array();
		foreach ($commandMethodReflection->getTagValues('see') as $tagValue) {
			if (preg_match('/^[\w\d\.]+:[\w\d]+:[\w\d]+$/', $tagValue) === 1) {
				$relatedCommandIdentifiers[] = $tagValue;
			}
		}
		return $relatedCommandIdentifiers;
	}

	/**
	 * @return \TYPO3\Flow\Reflection\MethodReflection
	 */
	protected function getCommandMethodReflection() {
		if ($this->commandMethodReflection === NULL) {
			$this->commandMethodReflection = new \TYPO3\Flow\Reflection\MethodReflection($this->controllerClassName, $this->controllerCommandName . 'Command');
		}
		return $this->commandMethodReflection;
	}
}
