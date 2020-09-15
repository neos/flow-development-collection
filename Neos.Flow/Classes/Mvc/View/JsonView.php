<?php
namespace Neos\Flow\Mvc\View;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;

/**
 * A JSON view
 *
 * @api
 */
class JsonView extends AbstractView
{
    /**
     * Supported options
     * @var array
     */
    protected $supportedOptions = [
        'jsonEncodingOptions' => [0, 'Bitmask of supported Encoding options. See http://php.net/manual/en/json.constants.php', 'integer']
    ];

    /**
     * Definition for the class name exposure configuration,
     * that is, if the class name of an object should also be
     * part of the output JSON, if configured.
     *
     * Setting this value, the object's class name is fully
     * put out, including the namespace.
     */
    const EXPOSE_CLASSNAME_FULLY_QUALIFIED = 1;

    /**
     * Puts out only the actual class name without namespace.
     * See EXPOSE_CLASSNAME_FULL for the meaning of the constant at all.
     */
    const EXPOSE_CLASSNAME_UNQUALIFIED = 2;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * Only variables whose name is contained in this array will be rendered
     *
     * @var array
     */
    protected $variablesToRender = ['value'];

    /**
     * The rendering configuration for this JSON view which
     * determines which properties of each variable to render.
     *
     * The configuration array must have the following structure:
     *
     * Example 1:
     *
     * array(
     * 		'variable1' => array(
     * 			'_only' => array('property1', 'property2', ...)
     * 		),
     * 		'variable2' => array(
     * 	 		'_exclude' => array('property3', 'property4, ...)
     * 		),
     * 		'variable3' => array(
     * 			'_exclude' => array('secretTitle'),
     * 			'_descend' => array(
     * 				'customer' => array(
     * 					'_only' => array('firstName', 'lastName')
     * 				)
     * 			)
     * 		),
     * 		'somearrayvalue' => array(
     * 			'_descendAll' => array(
     * 				'_only' => array('property1')
     * 			)
     * 		)
     * )
     *
     * Of variable1 only property1 and property2 will be included.
     * Of variable2 all properties except property3 and property4
     * are used.
     * Of variable3 all properties except secretTitle are included.
     *
     * If a property value is an array or object, it is not included
     * by default. If, however, such a property is listed in a "_descend"
     * section, the renderer will descend into this sub structure and
     * include all its properties (of the next level).
     *
     * The configuration of each property in "_descend" has the same syntax
     * like at the top level. Therefore - theoretically - infinitely nested
     * structures can be configured.
     *
     * To export indexed arrays the "_descendAll" section can be used to
     * include all array keys for the output. The configuration inside a
     * "_descendAll" will be applied to each array element.
     *
     *
     * Example 2: exposing object identifier
     *
     * array(
     *		'variableFoo' => array(
     *			'_exclude' => array('secretTitle'),
     *			'_descend' => array(
     *				'customer' => array(    // consider 'customer' being a persisted entity
     *					'_only' => array('firstName'),
     * 					'_exposeObjectIdentifier' => true,
     * 					'_exposedObjectIdentifierKey' => 'guid'
     *				)
     *			)
     *		)
     * )
     *
     * Note for entity objects you are able to expose the object's identifier
     * also, just add an "_exposeObjectIdentifier" directive set to true and
     * an additional property '__identity' will appear keeping the persistence
     * identifier. Renaming that property name instead of '__identity' is also
     * possible with the directive "_exposedObjectIdentifierKey".
     * Example 2 above would output (summarized):
     * {"customer":{"firstName":"John","guid":"892693e4-b570-46fe-af71-1ad32918fb64"}}
     *
     *
     * Example 3: exposing object's class name
     *
     * array(
     *		'variableFoo' => array(
     *			'_exclude' => array('secretTitle'),
     *			'_descend' => array(
     *				'customer' => array(    // consider 'customer' being an object
     *					'_only' => array('firstName'),
     * 					'_exposeClassName' => Neos\Flow\Mvc\View\JsonView::EXPOSE_CLASSNAME_FULLY_QUALIFIED
     *				)
     *			)
     *		)
     * )
     *
     * The ``_exposeClassName`` is similar to the objectIdentifier one, but the class name is added to the
     * JSON object output, for example (summarized):
     * {"customer":{"firstName":"John","__class":"Acme\Foo\Domain\Model\Customer"}}
     *
     * The other option is EXPOSE_CLASSNAME_UNQUALIFIED which only will give the last part of the class
     * without the namespace, for example (summarized):
     * {"customer":{"firstName":"John","__class":"Customer"}}
     * This might be of interest to not provide information about the package or domain structure behind.
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * Specifies which variables this JsonView should render
     * By default only the variable 'value' will be rendered
     *
     * @param array $variablesToRender
     * @return void
     * @api
     */
    public function setVariablesToRender(array $variablesToRender)
    {
        $this->variablesToRender = $variablesToRender;
    }

    /**
     * @param array $configuration The rendering configuration for this JSON view
     * @return void
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Transforms the value view variable to a serializable
     * array represantion using a YAML view configuration and JSON encodes
     * the result.
     *
     * @return string The JSON encoded variables
     * @api
     */
    public function render()
    {
        $this->controllerContext->getResponse()->setContentType('application/json');
        $propertiesToRender = $this->renderArray();
        $options = $this->getOption('jsonEncodingOptions');
        return json_encode($propertiesToRender, $options);
    }

    /**
     * Loads the configuration and transforms the value to a serializable
     * array.
     *
     * @return array An array containing the values, ready to be JSON encoded
     * @api
     */
    protected function renderArray()
    {
        if (count($this->variablesToRender) === 1) {
            $variableName = current($this->variablesToRender);
            $valueToRender = isset($this->variables[$variableName]) ? $this->variables[$variableName] : null;
            $configuration = isset($this->configuration[$variableName]) ? $this->configuration[$variableName] : [];
        } else {
            $valueToRender = [];
            foreach ($this->variablesToRender as $variableName) {
                $valueToRender[$variableName] = isset($this->variables[$variableName]) ? $this->variables[$variableName] : null;
            }
            $configuration = $this->configuration;
        }
        return $this->transformValue($valueToRender, $configuration);
    }

    /**
     * Transforms a value depending on type recursively using the
     * supplied configuration.
     *
     * @param mixed $value The value to transform
     * @param array $configuration Configuration for transforming the value
     * @return array The transformed value
     */
    protected function transformValue($value, array $configuration)
    {
        if (is_array($value) || $value instanceof \ArrayAccess) {
            $array = [];
            foreach ($value as $key => $element) {
                if (isset($configuration['_descendAll']) && is_array($configuration['_descendAll'])) {
                    $array[$key] = $this->transformValue($element, $configuration['_descendAll']);
                } else {
                    if (isset($configuration['_only']) && is_array($configuration['_only']) && !in_array($key, $configuration['_only'])) {
                        continue;
                    }
                    if (isset($configuration['_exclude']) && is_array($configuration['_exclude']) && in_array($key, $configuration['_exclude'])) {
                        continue;
                    }
                    $array[$key] = $this->transformValue($element, isset($configuration[$key]) ? $configuration[$key] : []);
                }
            }
            return $array;
        } elseif (is_object($value) && $value instanceof \JsonSerializable) {
            return $this->transformValue($value->jsonSerialize(), $configuration);
        } elseif (is_object($value)) {
            return $this->transformObject($value, $configuration);
        } else {
            return $value;
        }
    }

    /**
     * Traverses the given object structure in order to transform it into an
     * array structure.
     *
     * @param object $object Object to traverse
     * @param array $configuration Configuration for transforming the given object or NULL
     * @return array Object structure as an array
     */
    protected function transformObject($object, array $configuration)
    {
        if ($object instanceof \DateTimeInterface) {
            return $object->format(\DateTime::ISO8601);
        } else {
            $propertyNames = ObjectAccess::getGettablePropertyNames($object);

            $propertiesToRender = [];
            foreach ($propertyNames as $propertyName) {
                if (isset($configuration['_only']) && is_array($configuration['_only']) && !in_array($propertyName, $configuration['_only'])) {
                    continue;
                }
                if (isset($configuration['_exclude']) && is_array($configuration['_exclude']) && in_array($propertyName, $configuration['_exclude'])) {
                    continue;
                }

                $propertyValue = ObjectAccess::getProperty($object, $propertyName);

                if (!is_array($propertyValue) && !is_object($propertyValue)) {
                    $propertiesToRender[$propertyName] = $propertyValue;
                } elseif (isset($configuration['_descend']) && array_key_exists($propertyName, $configuration['_descend'])) {
                    $propertiesToRender[$propertyName] = $this->transformValue($propertyValue, $configuration['_descend'][$propertyName]);
                }
            }
            if (isset($configuration['_exposeObjectIdentifier']) && $configuration['_exposeObjectIdentifier'] === true) {
                if (isset($configuration['_exposedObjectIdentifierKey']) && strlen($configuration['_exposedObjectIdentifierKey']) > 0) {
                    $identityKey = $configuration['_exposedObjectIdentifierKey'];
                } else {
                    $identityKey = '__identity';
                }
                $propertiesToRender[$identityKey] = $this->persistenceManager->getIdentifierByObject($object);
            }
            if (isset($configuration['_exposeClassName']) && ($configuration['_exposeClassName'] === self::EXPOSE_CLASSNAME_FULLY_QUALIFIED || $configuration['_exposeClassName'] === self::EXPOSE_CLASSNAME_UNQUALIFIED)) {
                $className = TypeHandling::getTypeForValue($object);
                $classNameParts = explode('\\', $className);
                $propertiesToRender['__class'] = ($configuration['_exposeClassName'] === self::EXPOSE_CLASSNAME_FULLY_QUALIFIED ? $className : array_pop($classNameParts));
            }

            return $propertiesToRender;
        }
    }
}
