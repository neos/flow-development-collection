Fluid ViewHelper Reference
==========================

This reference was automatically generated from code on 2012-07-18


f:alias
-------

Declares new variables which are aliases of other variables.
Takes a "map"-Parameter which is an associative array which defines the shorthand mapping.

The variables are only declared inside the <f:alias>...</f:alias>-tag. After the
closing tag, all declared variables are removed again.



Arguments
*********

* ``map`` (array): array that specifies which variables should be mapped to which alias




Examples
********

**Single alias**::

	<f:alias map="{x: 'foo'}">{x}</f:alias>


Expected result::

	foo


**Multiple mappings**::

	<f:alias map="{x: foo.bar.baz, y: foo.bar.baz.name}">
	  {x.name} or {y}
	</f:alias>


Expected result::

	[name] or [name]
	depending on {foo.bar.baz}




f:base
------

View helper which creates a <base href="..."></base> tag. The Base URI
is taken from the current request.
In FLOW3, you should always include this ViewHelper to make the links work.




Examples
********

**Example**::

	<f:base />


Expected result::

	<base href="http://yourdomain.tld/" />
	(depending on your domain)




f:comment
---------

This ViewHelper prevents rendering of any content inside the tag
Note: Contents of the comment will still be **parsed** thus throwing an
Exception if it contains syntax errors. You can put child nodes in
CDATA tags to avoid this.




Examples
********

**Commenting out fluid code**::

	Before
	<f:comment>
	  This is completely hidden.
	  <f:debug>This does not get parsed</f:debug>
	</f:comment>
	After


Expected result::

	Before
	After


**Prevent parsing**::

	<f:comment><![CDATA[
	 <f:some.invalid.syntax />
	]]></f:comment>




f:count
-------

This ViewHelper counts elements of the specified array or countable object.



Arguments
*********

* ``subject`` (array, *optional*): The array or \Countable to be counted




Examples
********

**Count array elements**::

	<f:count subject="{0:1, 1:2, 2:3, 3:4}" />


Expected result::

	4


**inline notation**::

	{objects -> f:count()}


Expected result::

	10 (depending on the number of items in {objects})




f:cycle
-------

This ViewHelper cycles through the specified values.
This can be often used to specify CSS classes for example.
**Note:** To achieve the "zebra class" effect in a loop you can also use the "iteration" argument of the **for** ViewHelper.



Arguments
*********

* ``values`` (array): The array or object implementing \ArrayAccess (for example \SplObjectStorage) to iterated over

* ``as`` (string): The name of the iteration variable




Examples
********

**Simple**::

	<f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo"><f:cycle values="{0: 'foo', 1: 'bar', 2: 'baz'}" as="cycle">{cycle}</f:cycle></f:for>


Expected result::

	foobarbazfoo


**Alternating CSS class**::

	<ul>
	  <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">
	    <f:cycle values="{0: 'odd', 1: 'even'}" as="zebraClass">
	      <li class="{zebraClass}">{foo}</li>
	    </f:cycle>
	  </f:for>
	</ul>


Expected result::

	<ul>
	  <li class="odd">1</li>
	  <li class="even">2</li>
	  <li class="odd">3</li>
	  <li class="even">4</li>
	</ul>




f:debug
-------

Viewhelper that outputs its childnodes with \TYPO3\var_dump()



Arguments
*********

* ``title`` (string, *optional*):

* ``typeOnly`` (boolean, *optional*): Whether only the type should be returned instead of the whole chain.




Examples
********

**inline notation and custom title**::

	{object -> f:debug(title: 'Custom title')}


Expected result::

	all properties of {object} nicely highlighted (with custom title)


**only output the type**::

	{object -> f:debug(typeOnly: 1)}


Expected result::

	the type or class name of {object}




f:else
------

Else-Branch of a condition. Only has an effect inside of "If". See the If-ViewHelper for documentation.




Examples
********

**Output content if condition is not met**::

	<f:if condition="{someCondition}">
	  <f:else>
	    condition was not true
	  </f:else>
	</f:if>


Expected result::

	Everything inside the "else" tag is displayed if the condition evaluates to FALSE.
	Otherwise nothing is outputted in this example.




f:flashMessages
---------------

View helper which renders the flash messages (if there are any) as an unsorted list.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``as`` (string, *optional*): The name of the current flashMessage variable for rendering inside

* ``severity`` (string, *optional*): severity of the messages (One of the \TYPO3\Flow\Error\Message::SEVERITY_* constants)

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Simple**::

	<f:flashMessages />


Expected result::

	<ul>
	  <li class="flashmessages-ok">Some Default Message</li>
	  <li class="flashmessages-warning">Some Warning Message</li>
	</ul>


**Output with css class**::

	<f:flashMessages class="specialClass" />


Expected result::

	<ul class="specialClass">
	  <li class="specialClass-ok">Default Message</li>
	  <li class="specialClass-notice"><h3>Some notice message</h3>With message title</li>
	</ul>


**Output flash messages as a list, with arguments and filtered by a severity**::

	<f:flashMessages severity="Warning" as="flashMessages">
		<dl class="messages">
		<f:for each="{flashMessages}" as="flashMessage">
			<dt>{flashMessage.code}</dt>
			<dd>{flashMessage}</dd>
		</f:for>
		</dl>
	</f:flashMessages>


Expected result::

	<dl class="messages">
		<dt>1013</dt>
		<dd>Some Warning Message.</dd>
	</dl>




f:for
-----

Loop view helper which can be used to interate over array.
Implements what a basic foreach()-PHP-method does.



Arguments
*********

* ``each`` (array): The array or \SplObjectStorage to iterated over

* ``as`` (string): The name of the iteration variable

* ``key`` (string, *optional*): The name of the variable to store the current array key

* ``reverse`` (boolean, *optional*): If enabled, the iterator will start with the last element and proceed reversely

* ``iteration`` (string, *optional*): The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)




Examples
********

**Simple Loop**::

	<f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">{foo}</f:for>


Expected result::

	1234


**Output array key**::

	<ul>
	  <f:for each="{fruit1: 'apple', fruit2: 'pear', fruit3: 'banana', fruit4: 'cherry'}" as="fruit" key="label">
	    <li>{label}: {fruit}</li>
	  </f:for>
	</ul>


Expected result::

	<ul>
	  <li>fruit1: apple</li>
	  <li>fruit2: pear</li>
	  <li>fruit3: banana</li>
	  <li>fruit4: cherry</li>
	</ul>


**Iteration information**::

	<ul>
	  <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo" iteration="fooIterator">
	    <li>Index: {fooIterator.index} Cycle: {fooIterator.cycle} Total: {fooIterator.total}{f:if(condition: fooIterator.isEven, then: ' Even')}{f:if(condition: fooIterator.isOdd, then: ' Odd')}{f:if(condition: fooIterator.isFirst, then: ' First')}{f:if(condition: fooIterator.isLast, then: ' Last')}</li>
	  </f:for>
	</ul>


Expected result::

	<ul>
	  <li>Index: 0 Cycle: 1 Total: 4 Odd First</li>
	  <li>Index: 1 Cycle: 2 Total: 4 Even</li>
	  <li>Index: 2 Cycle: 3 Total: 4 Odd</li>
	  <li>Index: 3 Cycle: 4 Total: 4 Even Last</li>
	</ul>




f:form
------





Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``action`` (string, *optional*): target action

* ``arguments`` (array, *optional*): additional arguments

* ``controller`` (string, *optional*): name of target controller

* ``package`` (string, *optional*): name of target package

* ``subpackage`` (string, *optional*): name of target subpackage

* ``object`` (mixed, *optional*): object to use for the form. Use in conjunction with the "property" attribute on the sub tags

* ``section`` (string, *optional*): The anchor to be added to the action URI (only active if $actionUri is not set)

* ``format`` (string, *optional*): The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)

* ``additionalParams`` (array, *optional*): additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)

* ``absolute`` (boolean, *optional*): If set, an absolute action URI is rendered (only active if $actionUri is not set)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set

* ``fieldNamePrefix`` (string, *optional*): Prefix that will be added to all field names within this form

* ``actionUri`` (string, *optional*): can be used to overwrite the "action" attribute of the form tag

* ``objectName`` (string, *optional*): name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName

* ``enctype`` (string, *optional*): MIME type with which the form is submitted

* ``method`` (string, *optional*): Transfer type (GET or POST)

* ``name`` (string, *optional*): Name of form

* ``onreset`` (string, *optional*): JavaScript: On reset of the form

* ``onsubmit`` (string, *optional*): JavaScript: On submit of the form

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




f:form.button
-------------

Creates a button.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``type`` (string, *optional*): Specifies the type of button (e.g. "button", "reset" or "submit")

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``autofocus`` (string, *optional*): Specifies that a button should automatically get focus when the page loads

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``form`` (string, *optional*): Specifies one or more forms the button belongs to

* ``formaction`` (string, *optional*): Specifies where to send the form-data when a form is submitted. Only for type="submit"

* ``formenctype`` (string, *optional*): Specifies how form-data should be encoded before sending it to a server. Only for type="submit" (e.g. "application/x-www-form-urlencoded", "multipart/form-data" or "text/plain")

* ``formmethod`` (string, *optional*): Specifies how to send the form-data (which HTTP method to use). Only for type="submit" (e.g. "get" or "post")

* ``formnovalidate`` (string, *optional*): Specifies that the form-data should not be validated on submission. Only for type="submit"

* ``formtarget`` (string, *optional*): Specifies where to display the response after submitting the form. Only for type="submit" (e.g. "_blank", "_self", "_parent", "_top", "framename")

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Defaults**::

	<f:form.button>Send Mail</f:form.button>


Expected result::

	<button type="submit" name="" value="">Send Mail</button>


**Disabled cancel button with some HTML5 attributes**::

	<f:form.button type="reset" name="buttonName" value="buttonValue" disabled="disabled" formmethod="post" formnovalidate="formnovalidate">Cancel</f:form.button>


Expected result::

	<button disabled="disabled" formmethod="post" formnovalidate="formnovalidate" type="reset" name="myForm[buttonName]" value="buttonValue">Cancel</button>




f:form.checkbox
---------------

View Helper which creates a simple checkbox (<input type="checkbox">).



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``checked`` (boolean, *optional*): Specifies that the input element should be preselected

* ``multiple`` (boolean, *optional*): Specifies whether this checkbox belongs to a multivalue (is part of a checkbox group)

* ``name`` (string, *optional*): Name of input tag

* ``value`` (string): Value of input tag. Required for checkboxes

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.checkbox name="myCheckBox" value="someValue" />


Expected result::

	<input type="checkbox" name="myCheckBox" value="someValue" />


**Preselect**::

	<f:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />


Expected result::

	<input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
	(depending on $object)


**Bind to object property**::

	<f:form.checkbox property="interests" value="TYPO3" />


Expected result::

	<input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
	(depending on property "interests")




f:form.hidden
-------------

Renders an <input type="hidden" ...> tag.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.hidden name="myHiddenValue" value="42" />


Expected result::

	<input type="hidden" name="myHiddenValue" value="42" />




f:form.password
---------------

View Helper which creates a simple Password Text Box (<input type="password">).



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``maxlength`` (int, *optional*): The maxlength attribute of the input field (will not be validated)

* ``readonly`` (string, *optional*): The readonly attribute of the input field

* ``size`` (int, *optional*): The size of the input field

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.password name="myPassword" />


Expected result::

	<input type="password" name="myPassword" value="default value" />




f:form.radio
------------

View Helper which creates a simple radio button (<input type="radio">).



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``checked`` (boolean, *optional*): Specifies that the input element should be preselected

* ``name`` (string, *optional*): Name of input tag

* ``value`` (string): Value of input tag. Required for radio buttons

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.radio name="myRadioButton" value="someValue" />


Expected result::

	<input type="radio" name="myRadioButton" value="someValue" />


**Preselect**::

	<f:form.radio name="myRadioButton" value="someValue" checked="{object.value} == 5" />


Expected result::

	<input type="radio" name="myRadioButton" value="someValue" checked="checked" />
	(depending on $object)


**Bind to object property**::

	<f:form.radio property="newsletter" value="1" /> yes
	<f:form.radio property="newsletter" value="0" /> no


Expected result::

	<input type="radio" name="user[newsletter]" value="1" checked="checked" /> yes
	<input type="radio" name="user[newsletter]" value="0" /> no
	(depending on property "newsletter")




f:form.select
-------------





Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``multiple`` (string, *optional*): if set, multiple select field

* ``size`` (string, *optional*): Size of input field

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``options`` (array): Associative array with internal IDs as key, and the values are displayed in the select box

* ``optionValueField`` (string, *optional*): If specified, will call the appropriate getter on each object to determine the value.

* ``optionLabelField`` (string, *optional*): If specified, will call the appropriate getter on each object to determine the label.

* ``sortByOptionLabel`` (boolean, *optional*): If true, List will be sorted by label.

* ``selectAllByDefault`` (boolean, *optional*): If specified options are selected if none was set before.

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``translate`` (array, *optional*): Configures translation of view helper output.




f:form.submit
-------------

Creates a submit button.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Defaults**::

	<f:form.submit value="Send Mail" />


Expected result::

	<input type="submit" />


**Dummy content for template preview**::

	<f:submit name="mySubmit" value="Send Mail"><button>dummy button</button></f:submit>


Expected result::

	<input type="submit" name="mySubmit" value="Send Mail" />




f:form.textarea
---------------

Textarea view helper.
The value of the text area needs to be set via the "value" attribute, as with all other form ViewHelpers.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``rows`` (int): The number of rows of a text area

* ``cols`` (int): The number of columns of a text area

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.textarea name="myTextArea" value="This is shown inside the textarea" />


Expected result::

	<textarea name="myTextArea">This is shown inside the textarea</textarea>




f:form.textfield
----------------

View Helper which creates a text field (<input type="text">).



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``required`` (boolean, *optional*): If the field is required or not

* ``type`` (string, *optional*): The field type, e.g. "text", "email", "url" etc.

* ``placeholder`` (string, *optional*): A string used as a placeholder for the value to enter

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``maxlength`` (int, *optional*): The maxlength attribute of the input field (will not be validated)

* ``readonly`` (string, *optional*): The readonly attribute of the input field

* ``size`` (int, *optional*): The size of the input field

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.textfield name="myTextBox" value="default value" />


Expected result::

	<input type="text" name="myTextBox" value="default value" />




f:form.upload
-------------

A view helper which generates an <input type="file"> HTML element.
Make sure to set enctype="multipart/form-data" on the form!

If a file has been uploaded successfully and the form is re-displayed due to validation errors,
this ViewHelper will render hidden fields that contain the previously generated resource so you
won't have to upload the file again.

You can use a separate ViewHelper to display previously uploaded resources in order to remove/replace them.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``name`` (string, *optional*): Name of input tag

* ``value`` (mixed, *optional*): Value of input tag

* ``property`` (string, *optional*): Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.

* ``disabled`` (string, *optional*): Specifies that the input element should be disabled when the page loads

* ``errorClass`` (string, *optional*): CSS class to set if there are errors for this view helper

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event




Examples
********

**Example**::

	<f:form.upload name="file" />


Expected result::

	<input type="file" name="file" />


**Multiple Uploads**::

	<f:form.upload property="attachments.0.originalResource" />
	<f:form.upload property="attachments.1.originalResource" />


Expected result::

	<input type="file" name="formObject[attachments][0][originalResource]">
	<input type="file" name="formObject[attachments][0][originalResource]">




f:form.validationResults
------------------------

Validation results view helper



Arguments
*********

* ``for`` (string, *optional*): The name of the error name (e.g. argument name or property name). This can also be a property path (like blog.title), and will then only display the validation errors of that property.

* ``as`` (string, *optional*): The name of the variable to store the current error




Examples
********

**Output error messages as a list**::

	<f:form.validationResults>
	  <f:if condition="{validationResults.flattenedErrors}">
	    <ul class="errors">
	      <f:for each="{validationResults.flattenedErrors}" as="errors" key="propertyPath">
	        <li>{propertyPath}
	          <ul>
	          <f:for each="{errors}" as="error">
	            <li>{error.code}: {error}</li>
	          </f:for>
	          </ul>
	        </li>
	      </f:for>
	    </ul>
	  </f:if>
	</f:form.validationResults>


Expected result::

	<ul class="errors">
	  <li>1234567890: Validation errors for argument "newBlog"</li>
	</ul>


**Output error messages for a single property**::

	<f:form.validationResults for="someProperty">
	  <f:if condition="{validationResults.flattenedErrors}">
	    <ul class="errors">
	      <f:for each="{validationResults.errors}" as="error">
	        <li>{error.code}: {error}</li>
	      </f:for>
	    </ul>
	  </f:if>
	</f:form.validationResults>


Expected result::

	<ul class="errors">
	  <li>1234567890: Some error message</li>
	</ul>




f:format.crop
-------------

Use this view helper to crop the text between its opening and closing tags.



Arguments
*********

* ``maxCharacters`` (integer): Place where to truncate the string

* ``append`` (string, *optional*): What to append, if truncation happened




Examples
********

**Defaults**::

	<f:format.crop maxCharacters="10">This is some very long text</f:format.crop>


Expected result::

	This is so...


**Custom suffix**::

	<f:format.crop maxCharacters="17" append=" [more]">This is some very long text</f:format.crop>


Expected result::

	This is some very [more]




f:format.currency
-----------------

Formats a given float to a currency representation.



Arguments
*********

* ``currencySign`` (string, *optional*): (optional) The currency sign, eg $ or €.

* ``decimalSeparator`` (string, *optional*): (optional) The separator for the decimal point.

* ``thousandsSeparator`` (string, *optional*): (optional) The thousands separator.




Examples
********

**Defaults**::

	<f:format.currency>123.456</f:format.currency>


Expected result::

	123,46


**All parameters**::

	<f:format.currency currencySign="$" decimalSeparator="." thousandsSeparator=",">54321</f:format.currency>


Expected result::

	54,321.00 $


**Inline notation**::

	{someNumber -> f:format.currency(thousandsSeparator: ',', currencySign: '€')}


Expected result::

	54,321,00 €
	(depending on the value of {someNumber})




f:format.date
-------------

Formats a \DateTime object.



Arguments
*********

* ``date`` (mixed, *optional*): either a \DateTime object or a string that is accepted by \DateTime constructor

* ``format`` (string, *optional*): Format String which is taken to format the Date/Time




Examples
********

**Defaults**::

	<f:format.date>{dateObject}</f:format.date>


Expected result::

	1980-12-13
	(depending on the current date)


**Custom date format**::

	<f:format.date format="H:i">{dateObject}</f:format.date>


Expected result::

	01:23
	(depending on the current time)


**strtotime string**::

	<f:format.date format="d.m.Y - H:i:s">+1 week 2 days 4 hours 2 seconds</f:format.date>


Expected result::

	13.12.1980 - 21:03:42
	(depending on the current time, see http://www.php.net/manual/en/function.strtotime.php)


**output date from unix timestamp**::

	<f:format.date format="d.m.Y - H:i:s">@{someTimestamp}</f:format.date>


Expected result::

	13.12.1980 - 21:03:42
	(depending on the current time. Don't forget the "@" in front of the timestamp see http://www.php.net/manual/en/function.strtotime.php)


**Inline notation**::

	{f:format.date(date: dateObject)}


Expected result::

	1980-12-13
	(depending on the value of {dateObject})


**Inline notation (2nd variant)**::

	{dateObject -> f:format.date()}


Expected result::

	1980-12-13
	(depending on the value of {dateObject})




f:format.htmlentitiesDecode
---------------------------





Arguments
*********

* ``value`` (string, *optional*): string to format

* ``keepQuotes`` (boolean, *optional*): if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)

* ``encoding`` (string, *optional*):




f:format.htmlentities
---------------------





Arguments
*********

* ``value`` (string, *optional*): string to format

* ``keepQuotes`` (boolean, *optional*): if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)

* ``encoding`` (string, *optional*):

* ``doubleEncode`` (boolean, *optional*): If FALSE existing html entities won't be encoded, the default is to convert everything.




f:format.htmlspecialchars
-------------------------





Arguments
*********

* ``value`` (string, *optional*): string to format

* ``keepQuotes`` (boolean, *optional*): if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)

* ``encoding`` (string, *optional*):

* ``doubleEncode`` (boolean, *optional*): If FALSE existing html entities won't be encoded, the default is to convert everything.




f:format.identifier
-------------------





Arguments
*********

* ``value`` (object, *optional*): the object to render the identifier for, or NULL if VH children should be used




f:format.nl2br
--------------






f:format.number
---------------





Arguments
*********

* ``decimals`` (int, *optional*): The number of digits after the decimal point

* ``decimalSeparator`` (string, *optional*): The decimal point character

* ``thousandsSeparator`` (string, *optional*): The character for grouping the thousand digits




f:format.padding
----------------





Arguments
*********

* ``padLength`` (integer): Length of the resulting string. If the value of pad_length is negative or less than the length of the input string, no padding takes place.

* ``padString`` (string, *optional*): The padding string

* ``padType`` (string, *optional*): Append the padding at this site (Possible values: right,left,both. Default: right)




f:format.printf
---------------

A view helper for formatting values with printf. Either supply an array for
the arguments or a single value.
See http://www.php.net/manual/en/function.sprintf.php



Arguments
*********

* ``arguments`` (array): The arguments for vsprintf




Examples
********

**Scientific notation**::

	<f:format.printf arguments="{number: 362525200}">%.3e</f:format.printf>


Expected result::

	3.625e+8


**Argument swapping**::

	<f:format.printf arguments="{0: 3, 1: 'Kasper'}">%2$s is great, TYPO%1$d too. Yes, TYPO%1$d is great and so is %2$s!</f:format.printf>


Expected result::

	Kasper is great, TYPO3 too. Yes, TYPO3 is great and so is Kasper!


**Single argument**::

	<f:format.printf arguments="{1: 'TYPO3'}">We love %s</f:format.printf>


Expected result::

	We love TYPO3


**Inline notation**::

	{someText -> f:format.printf(arguments: {1: 'TYPO3'})}


Expected result::

	We love TYPO3




f:format.raw
------------

Outputs an argument/value without any escaping. Is normally used to output
an ObjectAccessor which should not be escaped, but output as-is.

PAY SPECIAL ATTENTION TO SECURITY HERE (especially Cross Site Scripting),
as the output is NOT SANITIZED!



Arguments
*********

* ``value`` (mixed, *optional*): The value to output




Examples
********

**Child nodes**::

	<f:format.raw>{string}</f:format.raw>


Expected result::

	(Content of {string} without any conversion/escaping)


**Value attribute**::

	<f:format.raw value="{string}" />


Expected result::

	(Content of {string} without any conversion/escaping)


**Inline notation**::

	{string -> f:format.raw()}


Expected result::

	(Content of {string} without any conversion/escaping)




f:format.stripTags
------------------





Arguments
*********

* ``value`` (string, *optional*): string to format




f:format.urlencode
------------------





Arguments
*********

* ``value`` (string, *optional*): string to format




f:groupedFor
------------

Grouped loop view helper.
Loops through the specified values.

The groupBy argument also supports property paths.



Arguments
*********

* ``each`` (array): The array or \SplObjectStorage to iterated over

* ``as`` (string): The name of the iteration variable

* ``groupBy`` (string): Group by this property

* ``groupKey`` (string, *optional*): The name of the variable to store the current group




Examples
********

**Simple**::

	<f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color">
	  <f:for each="{fruitsOfThisColor}" as="fruit">
	    {fruit.name}
	  </f:for>
	</f:groupedFor>


Expected result::

	apple cherry strawberry banana


**Two dimensional list**::

	<ul>
	  <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color" groupKey="color">
	    <li>
	      {color} fruits:
	      <ul>
	        <f:for each="{fruitsOfThisColor}" as="fruit" key="label">
	          <li>{label}: {fruit.name}</li>
	        </f:for>
	      </ul>
	    </li>
	  </f:groupedFor>
	</ul>


Expected result::

	<ul>
	  <li>green fruits
	    <ul>
	      <li>0: apple</li>
	    </ul>
	  </li>
	  <li>red fruits
	    <ul>
	      <li>1: cherry</li>
	    </ul>
	    <ul>
	      <li>3: strawberry</li>
	    </ul>
	  </li>
	  <li>yellow fruits
	    <ul>
	      <li>2: banana</li>
	    </ul>
	  </li>
	</ul>




f:identity.json
---------------

Renders the identity of a persisted object (if it has an identity).
Useful for using the identity outside of the form view helpers
(e.g. JavaScript and AJAX).

Deprecated since 1.1.0. Use f:format.identifier and f:format.json
ViewHelpers instead.



Arguments
*********

* ``object`` (object, *optional*): The persisted object




Examples
********

**Single alias**::

	<f:persistence.identity object="{post.blog}" />


Expected result::

	97e7e90a-413c-44ef-b2d0-ddfa4387b5ca




f:if
----

This view helper implements an if/else condition.
Check TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::convertArgumentValue() to see how boolean arguments are evaluated

**Conditions:**

As a condition is a boolean value, you can just use a boolean argument.
Alternatively, you can write a boolean expression there.
Boolean expressions have the following form:
XX Comparator YY
Comparator is one of: ==, !=, <, <=, >, >= and %
The % operator converts the result of the % operation to boolean.

XX and YY can be one of:
- number
- Object Accessor
- Array
- a ViewHelper
Note: Strings at XX/YY are NOT allowed, however, for the time being,
a string comparison can be achieved with comparing arrays (see example
below).
::

  <f:if condition="{rank} > 100">
    Will be shown if rank is > 100
  </f:if>
  <f:if condition="{rank} % 2">
    Will be shown if rank % 2 != 0.
  </f:if>
  <f:if condition="{rank} == {k:bar()}">
    Checks if rank is equal to the result of the ViewHelper "k:bar"
  </f:if>
  <f:if condition="{0: foo.bar} == {0: 'stringToCompare'}">
    Will result true if {foo.bar}'s represented value equals 'stringToCompare'.
  </f:if>



Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``condition`` (boolean): View helper condition




Examples
********

**Basic usage**::

	<f:if condition="somecondition">
	  This is being shown in case the condition matches
	</f:if>


Expected result::

	Everything inside the <f:if> tag is being displayed if the condition evaluates to TRUE.


**If / then / else**::

	<f:if condition="somecondition">
	  <f:then>
	    This is being shown in case the condition matches.
	  </f:then>
	  <f:else>
	    This is being displayed in case the condition evaluates to FALSE.
	  </f:else>
	</f:if>


Expected result::

	Everything inside the "then" tag is displayed if the condition evaluates to TRUE.
	Otherwise, everything inside the "else"-tag is displayed.


**inline notation**::

	{f:if(condition: someCondition, then: 'condition is met', else: 'condition is not met')}


Expected result::

	The value of the "then" attribute is displayed if the condition evaluates to TRUE.
	Otherwise, everything the value of the "else"-attribute is displayed.




f:layout
--------

With this tag, you can select a layout to be used for the current template.



Arguments
*********

* ``name`` (string): Name of layout to use. If none given, "Default" is used.




f:link.action
-------------

A view helper for creating links to actions.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``controller`` (string, *optional*): Target controller. If NULL current controllerName is used

* ``package`` (string, *optional*): Target package. if NULL current package is used

* ``subpackage`` (string, *optional*): Target subpackage. if NULL current subpackage is used

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``additionalParams`` (array, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




Examples
********

**Defaults**::

	<f:link.action>some link</f:link.action>


Expected result::

	<a href="currentpackage/currentcontroller">some link</a>
	(depending on routing setup and current package/controller/action)


**Additional arguments**::

	<f:link.action action="myAction" controller="MyController" package="YourCompanyName.MyPackage" subpackage="YourCompanyName.MySubpackage" arguments="{key1: 'value1', key2: 'value2'}">some link</f:link.action>


Expected result::

	<a href="mypackage/mycontroller/mysubpackage/myaction?key1=value1&amp;key2=value2">some link</a>
	(depending on routing setup)




f:link.email
------------

Email link view helper.
Generates an email link.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``email`` (string): The email address to be turned into a link.

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




Examples
********

**basic email link**::

	<f:link.email email="foo@bar.tld" />


Expected result::

	<a href="mailto:foo@bar.tld">foo@bar.tld</a>


**Email link with custom linktext**::

	<f:link.email email="foo@bar.tld">some custom content</f:emaillink>


Expected result::

	<a href="mailto:foo@bar.tld">some custom content</a>




f:link.external
---------------

A view helper for creating links to external targets.



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``uri`` (string): the URI that will be put in the href attribute of the rendered link tag

* ``defaultScheme`` (string, *optional*): scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




Examples
********

**custom default scheme**::

	<f:link.external uri="typo3.org" defaultScheme="ftp">external ftp link</f:link.external>


Expected result::

	<a href="ftp://typo3.org">external ftp link</a>




f:widget.link
-------------

widget.link ViewHelper
This ViewHelper can be used inside widget templates in order to render links pointing to widget actions



Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``ajax`` (boolean, *optional*): TRUE if the URI should be to an AJAX widget, FALSE otherwise.

* ``includeWidgetContext`` (boolean, *optional*): TRUE if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




f:link.widget
-------------





Arguments
*********

* ``additionalAttributes`` (array, *optional*): Additional tag attributes. They will be added directly to the resulting HTML tag.

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``ajax`` (boolean, *optional*): TRUE if the URI should be to an AJAX widget, FALSE otherwise.

* ``includeWidgetContext`` (boolean, *optional*): TRUE if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)

* ``class`` (string, *optional*): CSS class(es) for this element

* ``dir`` (string, *optional*): Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)

* ``id`` (string, *optional*): Unique (in this file) identifier for this HTML element.

* ``lang`` (string, *optional*): Language for this element. Use short names specified in RFC 1766

* ``style`` (string, *optional*): Individual CSS styles for this element

* ``title`` (string, *optional*): Tooltip text of element

* ``accesskey`` (string, *optional*): Keyboard shortcut to access this element

* ``tabindex`` (integer, *optional*): Specifies the tab order of this element

* ``onclick`` (string, *optional*): JavaScript evaluated for the onclick event

* ``name`` (string, *optional*): Specifies the name of an anchor

* ``rel`` (string, *optional*): Specifies the relationship between the current document and the linked document

* ``rev`` (string, *optional*): Specifies the relationship between the linked document and the current document

* ``target`` (string, *optional*): Specifies where to open the linked document




f:renderChildren
----------------





Arguments
*********

* ``arguments`` (array, *optional*):




f:render
--------





Arguments
*********

* ``section`` (string, *optional*): Name of section to render. If used in a layout, renders a section of the main content file. If used inside a standard template, renders a section of the same file.

* ``partial`` (string, *optional*): Reference to a partial.

* ``arguments`` (array, *optional*): Arguments to pass to the partial.

* ``optional`` (boolean, *optional*): Set to TRUE, to ignore unknown sections, so the definition of a section inside a template can be optional for a layout




Examples
********

**Rendering partials**::

	<f:render partial="SomePartial" arguments="{foo: someVariable}" />


Expected result::

	the content of the partial "SomePartial". The content of the variable {someVariable} will be available in the partial as {foo}


**Rendering sections**::

	<f:section name="someSection">This is a section. {foo}</f:section>
	<f:render section="someSection" arguments="{foo: someVariable}" />


Expected result::

	the content of the section "someSection". The content of the variable {someVariable} will be available in the partial as {foo}


**Rendering recursive sections**::

	<f:section name="mySection">
	 <ul>
	   <f:for each="{myMenu}" as="menuItem">
	     <li>
	       {menuItem.text}
	       <f:if condition="{menuItem.subItems}">
	         <f:render section="mySection" arguments="{myMenu: menuItem.subItems}" />
	       </f:if>
	     </li>
	   </f:for>
	 </ul>
	</f:section>
	<f:render section="mySection" arguments="{myMenu: menu}" />


Expected result::

	<ul>
	  <li>menu1
	    <ul>
	      <li>menu1a</li>
	      <li>menu1b</li>
	    </ul>
	  </li>
	[...]
	(depending on the value of {menu})


**Passing all variables to a partial**::

	<f:render partial="somePartial" arguments="{_all}" />


Expected result::

	the content of the partial "somePartial".
	Using the reserved keyword "_all", all available variables will be passed along to the partial




f:section
---------





Arguments
*********

* ``name`` (string): Name of the section




Examples
********

**Rendering sections**::

	<f:section name="someSection">This is a section. {foo}</f:section>
	<f:render section="someSection" arguments="{foo: someVariable}" />


Expected result::

	the content of the section "someSection". The content of the variable {someVariable} will be available in the partial as {foo}


**Rendering recursive sections**::

	<f:section name="mySection">
	 <ul>
	   <f:for each="{myMenu}" as="menuItem">
	     <li>
	       {menuItem.text}
	       <f:if condition="{menuItem.subItems}">
	         <f:render section="mySection" arguments="{myMenu: menuItem.subItems}" />
	       </f:if>
	     </li>
	   </f:for>
	 </ul>
	</f:section>
	<f:render section="mySection" arguments="{myMenu: menu}" />


Expected result::

	<ul>
	  <li>menu1
	    <ul>
	      <li>menu1a</li>
	      <li>menu1b</li>
	    </ul>
	  </li>
	[...]
	(depending on the value of {menu})




f:security.ifAccess
-------------------

This view helper implements an ifAccess/else condition.



Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``resource`` (string): Policy resource




f:security.ifAuthenticated
--------------------------

This view helper implements an ifAuthenticated/else condition.



Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.




f:security.ifHasRole
--------------------

This view helper implements an ifHasRole/else condition.



Arguments
*********

* ``then`` (mixed, *optional*): Value to be returned if the condition if met.

* ``else`` (mixed, *optional*): Value to be returned if the condition if not met.

* ``role`` (string): The role




f:then
------






f:translate
-----------

Returns translated message using source message or key ID.

Also replaces all placeholders with formatted versions of provided values.



Arguments
*********

* ``id`` (string, *optional*): Id to use for finding translation (trans-unit id in XLIFF)

* ``value`` (string, *optional*): If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default

* ``arguments`` (array, *optional*): Numerically indexed array of values to be inserted into placeholders

* ``source`` (string, *optional*): Name of file with translations

* ``package`` (string, *optional*): Target package key. If not set, the current package key will be used

* ``quantity`` (mixed, *optional*): A number to find plural form for (float or int), NULL to not use plural forms

* ``locale`` (string, *optional*): An identifier of locale to use (NULL for use the default locale)




Examples
********

**Translation by id**::

	<f:translate id="user.unregistered">Unregistered User</f:translate>


Expected result::

	translation of label with the id "user.unregistered" and a fallback to "Unregistered User"


**Inline notation**::

	{f:translate(id: 'some.label.id', default: 'fallback result')}


Expected result::

	translation of label with the id "some.label.id" and a fallback to "fallback result"


**Custom source and locale**::

	<f:translate id="some.label.id" somesource="SomeLabelsCatalog" locale="de_DE"/>


Expected result::

	translation from custom source "SomeLabelsCatalog" for locale "de_DE"


**Custom source from other package**::

	<f:translate id="some.label.id" source="LabelsCatalog" package="OtherPackage"/>


Expected result::

	translation from custom source "LabelsCatalog" in "OtherPackage"


**Arguments**::

	<f:translate arguments="{0: 'foo', 1: '99.9'}">Untranslated {0} and {1,number}</f:translate>


Expected result::

	translation of the label "Untranslated foo and 99.9"


**Translation by label**::

	<f:translate>Untranslated label</f:translate>


Expected result::

	translation of the label "Untranslated label"




f:uri.action
------------

A view helper for creating URIs to actions.



Arguments
*********

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``controller`` (string, *optional*): Target controller. If NULL current controllerName is used

* ``package`` (string, *optional*): Target package. if NULL current package is used

* ``subpackage`` (string, *optional*): Target subpackage. if NULL current subpackage is used

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``additionalParams`` (array, *optional*): additional query parameters that won't be prefixed like $arguments (overrule $arguments)

* ``absolute`` (boolean, *optional*): If set, an absolute URI is rendered

* ``addQueryString`` (boolean, *optional*): If set, the current query parameters will be kept in the URI

* ``argumentsToBeExcludedFromQueryString`` (array, *optional*): arguments to be removed from the URI. Only active if $addQueryString = TRUE




Examples
********

**Defaults**::

	<f:uri.action>some link</f:uri.action>


Expected result::

	currentpackage/currentcontroller
	(depending on routing setup and current package/controller/action)


**Additional arguments**::

	<f:uri.action action="myAction" controller="MyController" package="YourCompanyName.MyPackage" subpackage="YourCompanyName.MySubpackage" arguments="{key1: 'value1', key2: 'value2'}">some link</f:uri.action>


Expected result::

	mypackage/mycontroller/mysubpackage/myaction?key1=value1&amp;key2=value2
	(depending on routing setup)




f:uri.email
-----------

Email uri view helper.
Currently the specified email is simply prepended by "mailto:" but we might add spam protection.



Arguments
*********

* ``email`` (string): The email address to be turned into a mailto uri.




Examples
********

**basic email uri**::

	<f:uri.email email="foo@bar.tld" />


Expected result::

	mailto:foo@bar.tld




f:uri.external
--------------

A view helper for creating URIs to external targets.
Currently the specified URI is simply passed through.



Arguments
*********

* ``uri`` (string): target URI

* ``defaultScheme`` (string, *optional*): scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already




Examples
********

**custom default scheme**::

	<f:uri.external uri="typo3.org" defaultScheme="ftp" />


Expected result::

	ftp://typo3.org




f:uri.resource
--------------

A view helper for creating URIs to resources.



Arguments
*********

* ``path`` (string, *optional*): The path and filename of the resource (relative to Public resource directory of the package)

* ``package`` (string, *optional*): Target package key. If not set, the current package key will be used

* ``resource`` (TYPO3\Flow\Resource\Resource, *optional*): If specified, this resource object is used instead of the path and package information

* ``uri`` (string, *optional*): A resource URI, a relative / absolute path or URL




Examples
********

**Defaults**::

	<link href="{f:uri.resource(path: 'CSS/Stylesheet.css')}" rel="stylesheet" />


Expected result::

	<link href="http://yourdomain.tld/_Resources/Static/YourPackage/CSS/Stylesheet.css" rel="stylesheet" />
	(depending on current package)


**Other package resource**::

	{f:uri.resource(path: 'gfx/SomeImage.png', package: 'DifferentPackage')}


Expected result::

	http://yourdomain.tld/_Resources/Static/DifferentPackage/gfx/SomeImage.png
	(depending on domain)


**Resource object**::

	<img src="{f:uri.resource(resource: myImage.resource)}" />


Expected result::

	<img src="http://yourdomain.tld/_Resources/Persistent/69e73da3ce0ad08c717b7b9f1c759182d6650944.jpg" />
	(depending on your resource object)




f:widget.uri
------------

widget.uri ViewHelper
This ViewHelper can be used inside widget templates in order to render URIs pointing to widget actions



Arguments
*********

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``ajax`` (boolean, *optional*): TRUE if the URI should be to an AJAX widget, FALSE otherwise.

* ``includeWidgetContext`` (boolean, *optional*): TRUE if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)




f:uri.widget
------------





Arguments
*********

* ``action`` (string, *optional*): Target action

* ``arguments`` (array, *optional*): Arguments

* ``section`` (string, *optional*): The anchor to be added to the URI

* ``format`` (string, *optional*): The requested format, e.g. ".html

* ``ajax`` (boolean, *optional*): TRUE if the URI should be to an AJAX widget, FALSE otherwise.

* ``includeWidgetContext`` (boolean, *optional*): TRUE if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)




f:widget.autocomplete
---------------------





Arguments
*********

* ``objects`` (TYPO3\Flow\Persistence\QueryResultInterface):

* ``for`` (string):

* ``searchProperty`` (string):

* ``configuration`` (array, *optional*):

* ``widgetId`` (string, *optional*): Unique identifier of the widget instance




f:widget.paginate
-----------------

This ViewHelper renders a Pagination of objects.



Arguments
*********

* ``objects`` (TYPO3\Flow\Persistence\QueryResultInterface):

* ``as`` (string):

* ``configuration`` (array, *optional*):

* ``widgetId`` (string, *optional*): Unique identifier of the widget instance




f:format.json
-------------

Wrapper for PHPs json_encode function.



Arguments
*********

* ``value`` (mixed, *optional*): The incoming data to convert, or NULL if VH children should be used

* ``forceObject`` (boolean, *optional*): Outputs an JSON object rather than an array




Examples
********

**encoding a view variable**::

	{someArray -> f:format.json()}


Expected result::

	["array","values"]
	// depending on the value of {someArray}


**associative array**::

	{f:format.json(value: {foo: 'bar', bar: 'baz'})}


Expected result::

	{"foo":"bar","bar":"baz"}


**non-associative array with forced object**::

	{f:format.json(value: {0: 'bar', 1: 'baz'}, forceObject: 1)}


Expected result::

	{"0":"bar","1":"baz"}



