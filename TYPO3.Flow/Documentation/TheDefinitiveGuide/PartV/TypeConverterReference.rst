TYPO3 Flow TypeConverter Reference
==================================

This reference was automatically generated from code on 2012-12-12


ArrayConverter
--------------

Converter which transforms arrays to arrays.

:Priority: 1
:Target type: array
:Source type: array





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

Converter which transforms a simple type to a float.

This is basically done by simply casting it, except you provide some configuration options
which will make this converter use Flow's locale parsing capabilities in order to respect
deviating decimal separators.

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
:Source type: array





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




SessionConverter
----------------

This converter transforms a session identifier into a real session object.

:Priority: 1
:Target type: TYPO3\Flow\Session\Session
:Source type: string





StringConverter
---------------

Converter which transforms simple types to a string.

:Priority: 1
:Target type: string
:Source types:
 * string
 * integer




UriTypeConverter
----------------

A type converter for converting URI strings to Http Uri objects

:Priority: 1
:Target type: TYPO3\Flow\Http\Uri
:Source type: string




