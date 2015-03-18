.. _TYPO3 Flow TypeConverter Reference:

TYPO3 Flow TypeConverter Reference
==================================

This reference was automatically generated from code on 2015-03-18


ArrayConverter
--------------

Converter which transforms various types to arrays.

* If the source is an array, it is returned unchanged.
* If the source is a string, is is converted depending on CONFIGURATION_STRING_FORMAT,
  which can be STRING_FORMAT_CSV or STRING_FORMAT_JSON. For CSV the delimiter can be
  set via CONFIGURATION_STRING_DELIMITER.
* If the source is a Resource object, it is converted to an array. The actual resource
  content is either embedded as base64-encoded data or saved to a file, depending on
  CONFIGURATION_RESOURCE_EXPORT_TYPE. For RESOURCE_EXPORT_TYPE_FILE the setting
  CONFIGURATION_RESOURCE_SAVE_PATH must be set as well.

:Priority: 1
:Target type: array
:Source types:
 * array
 * string
 * TYPO3\Flow\Resource\Resource




ArrayFromObjectConverter
------------------------

TypeConverter which converts generic objects to arrays by converting and returning

:Priority: 1
:Target type: array
:Source type: object





ArrayTypeConverter
------------------

Converts Doctrine collections to arrays

:Priority: 1
:Target type: array
:Source type: Doctrine\Common\Collections\Collection





BooleanConverter
----------------

Converter which transforms simple types to a boolean.

For boolean this is a no-op, integer and float are simply typecast to boolean.

Strings are converted to TRUE unless they are empry or match one of 'off', 'n', 'no', 'false' (case-insensitive).

:Priority: 1
:Target type: boolean
:Source types:
 * boolean
 * string
 * integer
 * float




CollectionConverter
-------------------

Converter which transforms strings and arrays into a Doctrine ArrayCollection.

The input will be transformed to the element type <T> given with the $targetType (Type<T>) using available
type converters and the result will be used to populate a Doctrine ArrayCollection.

:Priority: 1
:Target type: Doctrine\Common\Collections\Collection
:Source types:
 * string
 * array




DateTimeConverter
-----------------

Converter which transforms from string, integer and array into DateTime objects.

For integers the default is to treat them as a unix timestamp. If a format to cerate from is given, this will be
used instead.

If source is a string it is expected to be formatted according to DEFAULT_DATE_FORMAT. This default date format
can be overridden in the initialize*Action() method like this::

 $this->arguments['<argumentName>']
   ->getPropertyMappingConfiguration()
   ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
   ->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\DateTimeConverter', \TYPO3\Flow\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, '<dateFormat>');

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
 * integer
 * array




FloatConverter
--------------

Converter which transforms a float, integer or string to a float.

This is basically done by simply casting it, unless the input is a string and you provide some configuration
options which will make this converter use Flow's locale parsing capabilities in order to respect deviating
decimal separators.

Using NULL or an empty string as input will result in a NULL return value.

**Advanced usage in action controller context**

*Using default locale*::

 protected function initializeCreateAction() {
 	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 		'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', TRUE
 	);
 }

Just providing TRUE as option value will use the current default locale. In case that default locale is "DE"
for Germany for example, where a comma is used as decimal separator, the mentioned code will return
(float)15.5 when the input was (string)"15,50".

*Using arbitrary locale*::

 protected function initializeCreateAction() {
 	$this->arguments['newBid']->getPropertyMappingConfiguration()->forProperty('price')->setTypeConverterOption(
 		'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'locale', 'fr'
 	);
 }

**Parsing mode**

There are two parsing modes available, strict and lenient mode. Strict mode will check all constraints of the provided
format, and if any of them are not fulfilled, the conversion will not take place.
In Lenient mode the parser will try to extract the intended number from the string, even if it's not well formed.
Default for strict mode is TRUE.

*Example setting lenient mode (abridged)*::

 ->setTypeConverterOption(
 	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'strictMode', FALSE
 );

**Format type**

Format type can be decimal, percent or currency; represented as class constant FORMAT_TYPE_DECIMAL,
FORMAT_TYPE_PERCENT or FORMAT_TYPE_CURRENCY of class TYPO3\Flow\I18n\Cldr\Reader\NumbersReader.
Default, if none given, is FORMAT_TYPE_DECIMAL.

*Example setting format type `currency` (abridged)*::

 ->setTypeConverterOption(
 	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'formatType', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_TYPE_CURRENCY
 );

**Format length**

Format type can be default, full, long, medium or short; represented as class constant FORMAT_LENGTH_DEFAULT,
FORMAT_LENGTH_FULL, FORMAT_LENGTH_LONG etc., of class  TYPO3\Flow\I18n\Cldr\Reader\NumbersReader.
The format length has a technical background in the CLDR repository, and specifies whether a different number
pattern should be used. In most cases leaving this DEFAULT would be the correct choice.

*Example setting format length (abridged)*::

 ->setTypeConverterOption(
 	'TYPO3\Flow\Property\TypeConverter\FloatConverter', 'formatLength', \TYPO3\Flow\I18n\Cldr\Reader\NumbersReader::FORMAT_LENGTH_FULL
 );

:Priority: 1
:Target type: float
:Source types:
 * float
 * integer
 * string




IntegerConverter
----------------

Converter which transforms to an integer.

* If the source is an integer, it is returned unchanged.
* If the source a numeric string, it is cast to integer
* If the source is a DateTime instance, the UNIX timestamp is returned

:Priority: 1
:Target type: integer
:Source types:
 * integer
 * string
 * DateTime




LocaleTypeConverter
-------------------

Converter which transforms strings to a Locale object.

:Priority: 1
:Target type: TYPO3\Flow\I18n\Locale
:Source type: string





MediaTypeConverter
------------------

Converter which transforms strings to arrays using the configured strategy.
This TypeConverter is used by default to decode the content of a HTTP request and it currently supports json and xml
based media types as well as urlencoded content.

:Priority: -1
:Target type: array
:Source type: string





ObjectConverter
---------------

This converter transforms arrays to simple objects (POPO) by setting properties.

This converter will only be used on target types that are not entities or value objects (for those the
PersistentObjectConverter is used).

The target type can be overridden in the source by setting the __type key to the desired value.

The converter will return an instance of the target type with all properties given in the source array set to
the respective values. For the mechanics used to set the values see ObjectAccess::setProperty().

:Priority: 0
:Target type: object
:Source type: array





PersistentObjectConverter
-------------------------

This converter transforms arrays or strings to persistent objects. It does the following:

- If the input is string, it is assumed to be a UUID. Then, the object is fetched from persistence.
- If the input is array, we check if it has an identity property.

- If the input has NO identity property, but additional properties, we create a new object and return it.
  However, we only do this if the configuration option "CONFIGURATION_CREATION_ALLOWED" is TRUE.
- If the input has an identity property AND the configuration option "CONFIGURATION_IDENTITY_CREATION_ALLOWED" is set,
  we fetch the object from persistent or create a new object if none was found and then set the sub-properties.
- If the input has an identity property and NO additional properties, we fetch the object from persistence.
- If the input has an identity property AND additional properties, we fetch the object from persistence,
  and set the sub-properties. We only do this if the configuration option "CONFIGURATION_MODIFICATION_ALLOWED" is TRUE.

:Priority: 1
:Target type: object
:Source types:
 * string
 * array




PersistentObjectSerializer
--------------------------

This converter transforms persistent objects to strings by returning their (technical) identifier.

Unpersisted changes to an object are not serialized, because only the persistence identifier is taken into account
as the serialized value.

:Priority: 1
:Target type: string
:Source type: TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface





ResourceTypeConverter
---------------------

A type converter for converting strings, array and uploaded files to Resource objects.

Has two major working modes:

1. File Uploads by PHP

   In this case, the input array is expected to be a fresh file upload following the native PHP handling. The
   temporary upload file is then imported through the resource manager.

   To enable the handling of files that have already been uploaded earlier, the special fields ['submittedFile'],
   ['submittedFile']['filename'] and ['submittedFile']['hash'] are checked. If set, they are used to
   fetch a file that has already been uploaded even if no file has been actually uploaded in the current request.


2. Strings / arbitrary Arrays

   If the source

   - is an array and contains the key '__identity'

   the converter will find an existing resource with the given identity or continue and assign the given identity if
   CONFIGURATION_IDENTITY_CREATION_ALLOWED is set.

   - is a string looking like a SHA1 (40 characters [0-9a-f]) or
   - is an array and contains the key 'hash' with a value looking like a SHA1 (40 characters [0-9a-f])

   the converter will look up an existing Resource with that hash and return it if found. If that fails,
   the converter will try to import a file named like that hash from the configured CONFIGURATION_RESOURCE_LOAD_PATH.

   If no hash is given in an array source but the key 'data' is set, the content of that key is assumed a binary string
   and a Resource representing this content is created and returned.

   The imported Resource will be given a 'filename' if set in the source array in both cases (import from file or data).

:Priority: 1
:Target type: TYPO3\Flow\Resource\Resource
:Source types:
 * string
 * array




RoleConverter
-------------

This converter transforms strings to role instances

:Priority: 0
:Target type: TYPO3\Flow\Security\Policy\Role
:Source type: string





SessionConverter
----------------

This converter transforms a session identifier into a real session object.

Given a session ID this will return an instance of TYPO3\Flow\Session\Session.

:Priority: 1
:Target type: TYPO3\Flow\Session\Session
:Source type: string





StringConverter
---------------

Converter which transforms simple types to a string.

* If the source is a DateTime instance, it will be formatted as string. The format
  can be set via CONFIGURATION_DATE_FORMAT.
* If the source is an array, it will be converted to a CSV string or JSON, depending
  on CONFIGURATION_ARRAY_FORMAT.

For array to CSV string, the delimiter can be set via CONFIGURATION_CSV_DELIMITER.

:Priority: 1
:Target type: string
:Source types:
 * string
 * integer
 * float
 * boolean
 * array
 * DateTime




TypedArrayConverter
-------------------

Converter which recursively transforms typed arrays (array<T>).

This is a meta converter that will take an array and try to transform all elements in that array to
the element type <T> of the target array using an available type converter.

:Priority: 2
:Target type: array
:Source type: array





UriTypeConverter
----------------

A type converter for converting URI strings to Http Uri objects.

This converter simply creates a TYPO3\Flow\Http\Uri instance from the source string.

:Priority: 1
:Target type: TYPO3\Flow\Http\Uri
:Source type: string




