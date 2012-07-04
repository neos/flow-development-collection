FLOW3 TypeConverter Reference
=============================

This reference was automatically generated from code on 2012-07-04


ArrayConverter
--------------

Converter which transforms arrays to arrays.

:Priority: 1
:Target type: array
:Source type: Array





BooleanConverter
----------------

Converter which transforms simple types to a boolean, by simply casting it.

:Priority: 1
:Target type: boolean
:Source types:
 * boolean
 * string




CollectionConverter
-------------------

Converter which transforms simple types to a Doctrine ArrayCollection.

:Priority: 1
:Target type: Doctrine\Common\Collections\Collection
:Source types:
 * string
 * array




DateTimeConverter
-----------------

Converter which transforms from different input formats into DateTime objects.

Source can be either a string or an array. The date string is expected to be formatted
according to DEFAULT_DATE_FORMAT.

But the default date format can be overridden in the initialize*Action() method like this::

 $this->arguments['<argumentName>']
   ->getPropertyMappingConfiguration()
   ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
   ->setTypeConverterOption('TYPO3\FLOW3\Property\TypeConverter\DateTimeConverter', \TYPO3\FLOW3\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, '<dateFormat>');

If the source is of type array, it is possible to override the format in the source::

 array(
  'date' => '<dateString>',
  'dateFormat' => '<dateFormat>'
 );

By using an array as source you can also override time and timezone of the created DateTime object::

 array(
  'date' => '<dateString>',
  'hour' => '<hour>', // integer
  'minute' => '<minute>', // integer
  'seconds' => '<seconds>', // integer
  'timezone' => '<timezone>', // string, see http://www.php.net/manual/timezones.php
 );

As an alternative to providing the date as string, you might supply day, month and year as array items each::

 array(
  'day' => '<day>', // integer
  'month' => '<month>', // integer
  'year' => '<year>', // integer
 );

:Priority: 1
:Target type: DateTime
:Source types:
 * string
 * array




FloatConverter
--------------

Converter which transforms a simple type to a float, by simply casting it.

:Priority: 1
:Target type: float
:Source types:
 * float
 * integer
 * string




IntegerConverter
----------------

Converter which transforms a simple type to an integer, by simply casting it.

:Priority: 1
:Target type: integer
:Source types:
 * integer
 * string




ObjectConverter
---------------

This converter transforms arrays to simple objects (POPO) by setting properties.

:Priority: 0
:Target type: object
:Source type: Array





PersistentObjectConverter
-------------------------

This converter transforms arrays or strings to persistent objects. It does the following:

- If the input is string, it is assumed to be a UUID. Then, the object is fetched from persistence.
- If the input is array, we check if it has an identity property.

- If the input has an identity property and NO additional properties, we fetch the object from persistence.
- If the input has an identity property AND additional properties, we fetch the object from persistence,
  and set the sub-properties. We only do this if the configuration option "CONFIGURATION_MODIFICATION_ALLOWED" is TRUE.
- If the input has NO identity property, but additional properties, we create a new object and return it.
  However, we only do this if the configuration option "CONFIGURATION_CREATION_ALLOWED" is TRUE.

:Priority: 1
:Target type: object
:Source types:
 * string
 * array




StringConverter
---------------

Converter which transforms simple types to a string.

:Priority: 1
:Target type: string
:Source types:
 * string
 * integer



