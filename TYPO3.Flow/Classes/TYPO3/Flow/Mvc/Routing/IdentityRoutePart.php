<?php
namespace TYPO3\Flow\Mvc\Routing;

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
use TYPO3\Flow\Mvc\Exception\InfiniteLoopException;
use TYPO3\Flow\Mvc\Exception\InvalidUriPatternException;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Identity Route Part
 * This route part can be used to create and resolve ObjectPathMappings.
 * This handler is used by default, if an objectType is specified for a route part in the routing configuration:
 * -
 *   name: 'Some route for xyz entities'
 *   uriPattern: '{xyz}'
 *   routeParts:
 *     'xyz':
 *       objectType: 'Some\Package\Domain\Model\Xyz'
 *
 * @see \TYPO3\Flow\Mvc\Routing\ObjectPathMapping
 * @api
 */
class IdentityRoutePart extends DynamicRoutePart {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\ObjectPathMappingRepository
	 * @Flow\Inject
	 */
	protected $objectPathMappingRepository;

	/**
	 * The object type (class name) of the entity this route part belongs to
	 *
	 * @var string
	 */
	protected $objectType;

	/**
	 * pattern for the URI representation (for example "{date:Y}/{date:m}/{date.d}/{title}")
	 *
	 * @var string
	 */
	protected $uriPattern = NULL;

	/**
	 * @param string $objectType
	 * @return void
	 */
	public function setObjectType($objectType) {
		$this->objectType = $objectType;
	}

	/**
	 * @return string
	 */
	public function getObjectType() {
		return $this->objectType;
	}

	/**
	 * @param string $uriPattern
	 * @return void
	 */
	public function setUriPattern($uriPattern) {
		$this->uriPattern = $uriPattern;
	}

	/**
	 * If $this->uriPattern is specified, this will be returned, otherwise identity properties of $this->objectType
	 * are returned in the format {property1}/{property2}/{property3}.
	 * If $this->objectType does not contain identity properties, an empty string is returned.
	 *
	 * @return string
	 */
	public function getUriPattern() {
		if ($this->uriPattern === NULL) {
			$classSchema = $this->reflectionService->getClassSchema($this->objectType);
			$identityProperties = $classSchema->getIdentityProperties();
			if (count($identityProperties) === 0) {
				$this->uriPattern = '';
			} else {
				$this->uriPattern = '{' . implode('}/{', array_keys($identityProperties)) . '}';
			}
		}
		return $this->uriPattern;
	}

	/**
	 * Checks, whether given value can be matched.
	 * If the value is empty, FALSE is returned.
	 * Otherwise the ObjectPathMappingRepository is asked for a matching ObjectPathMapping.
	 * If that is found the identifier is stored in $this->value, otherwise this route part does not match.
	 *
	 * @param string $value value to match, usually the current query path segment(s)
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE
	 * @api
	 */
	protected function matchValue($value) {
		if ($value === NULL || $value === '') {
			return FALSE;
		}
		$identifier = $this->getObjectIdentifierFromPathSegment($value);
		if ($identifier === NULL) {
			return FALSE;
		}
		$this->value = array('__identity' => $identifier);
		return TRUE;
	}

	/**
	 * Retrieves the object identifier from the given $pathSegment.
	 * If no UriPattern is set, the $pathSegment is expected to be the (URL-encoded) identifier otherwise a matching ObjectPathMapping fetched from persistence
	 * If no matching ObjectPathMapping was found or the given $pathSegment is no valid identifier NULL is returned.
	 *
	 * @param string $pathSegment the query path segment to convert
	 * @return string the technical identifier of the object or NULL if it couldn't be found
	 */
	protected function getObjectIdentifierFromPathSegment($pathSegment) {
		if ($this->getUriPattern() === '') {
			$identifier = urldecode($pathSegment);
			$object = $this->persistenceManager->getObjectByIdentifier($identifier, $this->objectType);
			if ($object !== NULL) {
				return $identifier;
			}
		} else {
			$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndPathSegment($this->objectType, $this->getUriPattern(), $pathSegment, !$this->lowerCase);
			if ($objectPathMapping !== NULL) {
				return $objectPathMapping->getIdentifier();
			}
		}
		return NULL;
	}

	/**
	 * Returns the first part of $routePath that should be evaluated in matchValue().
	 * If no split string is set (route part is the last in the routes uriPattern), the complete $routePart is returned.
	 * Otherwise the part is returned that matches the specified uriPattern of this route part.
	 *
	 * @param string $routePath The request path to be matched
	 * @return string value to match, or an empty string if $routePath is empty, split string was not found or uriPattern could not be matched
	 * @api
	 */
	protected function findValueToMatch($routePath) {
		if (!isset($routePath) || $routePath === '' || $routePath[0] === '/') {
			return '';
		}
		if ($this->getUriPattern() === '') {
			return parent::findValueToMatch($routePath);
		}
		$regexPattern = preg_quote($this->getUriPattern(), '/');
		$regexPattern = preg_replace('/\\\\{[^}]+\\\\}/', '[^\/]+', $regexPattern);
		if ($this->splitString !== '') {
			$regexPattern .= '(?=' . preg_quote($this->splitString, '/') . ')';
		}
		$matches = array();
		preg_match('/^' . $regexPattern . '/', trim($routePath, '/'), $matches);
		return isset($matches[0]) ? $matches[0] : '';
	}

	/**
	 * Resolves the given entity and sets the value to a URI representation (path segment) that matches $this->uriPattern and is unique for the given object.
	 *
	 * @param mixed $value
	 * @return boolean TRUE if the object could be resolved and stored in $this->value, otherwise FALSE.
	 */
	protected function resolveValue($value) {
		$identifier = NULL;
		if (is_array($value) && isset($value['__identity'])) {
			$identifier = $value['__identity'];
		} elseif ($value instanceof $this->objectType) {
			$identifier = $this->persistenceManager->getIdentifierByObject($value);
		}
		if ($identifier === NULL) {
			return FALSE;
		}
		$pathSegment = $this->getPathSegmentByIdentifier($identifier);
		if ($pathSegment === NULL) {
			return FALSE;
		}
		$this->value = $pathSegment;
		return TRUE;
	}

	/**
	 * Generates a unique string for the given identifier according to $this->uriPattern.
	 * If no UriPattern is set, the path segment is equal to the (URL-encoded) $identifier - otherwise a matching
	 * ObjectPathMapping is fetched from persistence.
	 * If no ObjectPathMapping exists for the given identifier, a new ObjectPathMapping is created.
	 *
	 * @param string $identifier the technical identifier of the object
	 * @return string the resolved path segment(s)
	 * @throws InfiniteLoopException if no unique path segment could be found after 100 iterations
	 */
	protected function getPathSegmentByIdentifier($identifier) {
		if ($this->getUriPattern() === '') {
			return urlencode($identifier);
		}

		$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndIdentifier($this->objectType, $this->getUriPattern(), $identifier);
		if ($objectPathMapping !== NULL) {
			return $this->lowerCase ? strtolower($objectPathMapping->getPathSegment()) : $objectPathMapping->getPathSegment();
		}
		$object = $this->persistenceManager->getObjectByIdentifier($identifier, $this->objectType);
		$pathSegment = $uniquePathSegment = $this->createPathSegmentForObject($object);
		$pathSegmentLoopCount = 0;
		do {
			if ($pathSegmentLoopCount++ > 99) {
				throw new InfiniteLoopException('No unique path segment could be found after ' . ($pathSegmentLoopCount - 1) . ' iterations.', 1316441798);
			}
			if ($uniquePathSegment !== '') {
				$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndPathSegment($this->objectType, $this->getUriPattern(), $uniquePathSegment, !$this->lowerCase);
				if ($objectPathMapping === NULL) {
					$this->storeObjectPathMapping($uniquePathSegment, $identifier);
					break;
				}
			}
			$uniquePathSegment = sprintf('%s-%d', $pathSegment, $pathSegmentLoopCount);
		} while (TRUE);

		return $this->lowerCase ? strtolower($uniquePathSegment) : $uniquePathSegment;
	}

	/**
	 * Creates a URI representation (path segment) for the given object matching $this->uriPattern.
	 *
	 * @param mixed $object object of type $this->objectType
	 * @return string URI representation (path segment) of the given object
	 * @throws InvalidUriPatternException
	 */
	protected function createPathSegmentForObject($object) {
		$matches = array();
		preg_match_all('/(?P<dynamic>{?)(?P<content>[^}{]+)}?/', $this->getUriPattern(), $matches, PREG_SET_ORDER);
		$pathSegment = '';
		foreach ($matches as $match) {
			if (empty($match['dynamic'])) {
				$pathSegment .= $match['content'];
			} else {
				$dynamicPathSegmentParts = explode(':', $match['content']);
				$propertyPath = $dynamicPathSegmentParts[0];
				$dynamicPathSegment = ObjectAccess::getPropertyPath($object, $propertyPath);
				if (is_object($dynamicPathSegment)) {
					if ($dynamicPathSegment instanceof \DateTime) {
						$dateFormat = isset($dynamicPathSegmentParts[1]) ? trim($dynamicPathSegmentParts[1]) : 'Y-m-d';
						$pathSegment .= $this->rewriteForUri($dynamicPathSegment->format($dateFormat));
					} else {
						throw new InvalidUriPatternException(sprintf('Invalid uriPattern "%s" for route part "%s". Property "%s" must be of type string or \DateTime. "%s" given.', $this->getUriPattern(), $this->getName(), $propertyPath, is_object($dynamicPathSegment) ? get_class($dynamicPathSegment) : gettype($dynamicPathSegment)), 1316442409);
					}
				} else {
					$pathSegment .= $this->rewriteForUri($dynamicPathSegment);
				}
			}
		}
		return $pathSegment;
	}

	/**
	 * Creates a new ObjectPathMapping and stores it in the repository
	 *
	 * @param string $pathSegment
	 * @param mixed $identifier
	 * @return void
	 */
	protected function storeObjectPathMapping($pathSegment, $identifier) {
		$objectPathMapping = new ObjectPathMapping();
		$objectPathMapping->setObjectType($this->objectType);
		$objectPathMapping->setUriPattern($this->getUriPattern());
		$objectPathMapping->setPathSegment($pathSegment);
		$objectPathMapping->setIdentifier($identifier);
		$this->objectPathMappingRepository->add($objectPathMapping);
		// TODO can be removed, when persistence manager has some memory cache
		$this->persistenceManager->persistAll();
	}

	/**
	 * Transforms the given string into a URI compatible format without special characters.
	 * In the long term this should be done with proper transliteration
	 *
	 * @param string $value
	 * @return string
	 * @todo use transliteration of the I18n sub package
	 */
	protected function rewriteForUri($value) {
		$transliteration = array(
			'ä' => 'ae',
			'Ä' => 'Ae',
			'ö' => 'oe',
			'Ö' => 'Oe',
			'ü' => 'ue',
			'Ü' => 'Ue',
			'ß' => 'ss',
		);
		$value = strtr($value, $transliteration);

		$spaceCharacter = '-';
		$value = preg_replace('/[ \-+_]+/', $spaceCharacter, $value);

		$value = preg_replace('/[^-a-z0-9.\\' . $spaceCharacter . ']/i', '', $value);

		$value = preg_replace('/\\' . $spaceCharacter . '{2,}/', $spaceCharacter, $value);
		$value = trim($value, $spaceCharacter);

		return $value;
	}


}
?>