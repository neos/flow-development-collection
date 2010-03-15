<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Container;

if (\F3\FLOW3\Core\Bootstrap::REVISION === '###FLOW3_REVISION###') {

	/**
	 * A auto-generated static dependency injection container.
	 *
	 * Note that this class might contain cryptic abbreviations and variables because
	 * it is not meant to be read by humans but rather be tailored to perform well.
	 *
	 */
	class StaticObjectContainer extends \F3\FLOW3\Object\Container\AbstractObjectContainer {

		/**
		 * An array of all registered objects and some additional information.
		 *
		 * For performance reasons, this array contains a few cryptic key values which
		 * have the following meaning:
		 *
		 * $objects['F3\MyPackage\MyObject'] => array(
		 *    'l' => 'f3\mypackage\myobject',            // the lowercased object name
		 *    's' => self::SCOPE_PROTOTYPE,              // the scope
		 *    'm' => 'b45',                              // name of the internal build method
		 *    'i' => object                              // the instance (singleton & session only)
		 * );
		 *
		 * @var array
		 */
		protected $objects = array(###OBJECTS_ARRAY###);

		/**
		 * @var F3\FLOW3\Session\SessionInterface
		 */
		protected $session;

		/**
		 *
		 * @var F3\FLOW3\Object\ObjectSerializer
		 */
		protected $objectSerializer;

		/**
		 * Initializes the session and loads all existing instances of scope session.
		 *
		 * @return void
		 * @author Robert Lemke <robert@typo3.org>
		 */
		public function initializeSession() {
			$this->objectSerializer = $this->get('F3\FLOW3\Object\ObjectSerializer');
			$this->session = $this->get('F3\FLOW3\Session\SessionInterface');
			$this->session->start();

			if ($this->session->hasKey('F3_FLOW3_Object_ObjectContainer') === TRUE) {
				$objectsAsArray = $this->session->getData('F3_FLOW3_Object_ObjectContainer');
				if (is_array($objectsAsArray)) {
					foreach ($this->objectSerializer->deserializeObjectsArray($objectsAsArray) as $objectName => $object) {
						if (isset($this->objects[$objectName])) {
							$this->objects[$objectName]['i'] = $object;
						}
					}
				}
			}
		}

		/**
		 * Imports object instances and shutdown objects from a Dynamic Container
		 *
		 * @param \F3\FLOW3\Object\Container\DynamicObjectContainer
		 * @return void
		 * @author Robert Lemke <robert@typo3.org>
		 */
		public function import(\F3\FLOW3\Object\Container\DynamicObjectContainer $dynamicObjectContainer) {
			foreach ($dynamicObjectContainer->getInstances() as $objectName => $instance) {
				if (!isset($this->objects[$objectName])) {
					throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Object "'. $objectName . '" is not known in the static object container.', 1265210342);
				}
				$this->objects[$objectName]['i'] = $instance;
			}
			$this->shutdownObjects = $dynamicObjectContainer->getShutdownObjects();
		}

		/**
		 * Shuts down this Object Container by calling the shutdown methods of all
		 * object instances which were configured to be shut down.
		 *
		 * @return void
		 * @author Robert Lemke <robert@typo3.org>
		 */
		public function shutdown() {
			foreach ($this->shutdownObjects as $object) {
				$methodName = $this->shutdownObjects[$object];
				$object->$methodName();
			}

			$this->objectSerializer->clearState();

			$objectsAsArray = array();
			foreach($this->objects as $objectName => $information) {
				if ($information['s'] === self::SCOPE_SESSION && isset($information['i'])) {
					$objectsAsArray = array_merge($objectsAsArray, $this->objectSerializer->serializeObjectAsPropertyArray($objectName, $information['i']));
				}
			}
			$this->session->putData('F3_FLOW3_Object_ObjectContainer', $objectsAsArray);
			$this->session->close();
		}


	###BUILD_METHODS###

	}
}
?>