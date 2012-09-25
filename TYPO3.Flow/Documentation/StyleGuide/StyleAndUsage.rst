Style and Usage
===============

.. highlight:: text

This chapter provides guidelines on writing style and usage. The intent of these
guidelines is to help maintain a consistent voice in publications of the TYPO3 Project and
in the user interface.


File Types
----------

Use all caps for abbreviations of file types::

	a PHP file, a YAML file, the RST file

Filename extensions, which indicate the file type, should be in lowercase::

	.php, .jpg, .css

Abbreviations and Acronyms
--------------------------

An *acronym* is a pronounceable word formed from the initial letter or letters of major
parts of a compound term. An *abbreviation* is usually formed in the same way but is not
pronounced as a word.

Abbreviations are often lowercase or a mix of lowercase and uppercase. Acronyms are
almost always all caps, regardless of the capitalization style of the spelled-out form.

* *Latin*: Avoid using Latin abbreviations.

	* *Correct*: ``for example, and others, and so on,`` and ``that is,`` or equivalent phrases
	* *Incorrect*: ``e.g.`` (for example), ``et al.`` (and others), ``etc.``  (and so on), ``i.e.`` (that is)

Above
-----

You can use ``above`` to describe an element or section of an onscreen document
that cannot be paged through (such as a single webpage).

Don’t use ``above`` in print documents; instead, use one of these styles:

- Earlier chapter: Use the chapter name and number::

	To learn how to create movies, see Chapter 4, “Composing Movies.”

- Earlier section: Use the section name followed by the page number::

	For more information, see “Printing” on page 154.

- Earlier figure, table, or code listing: Use the number of the element followed by the page number::

	For a summary of slot and drive numbers, see Table 1-2 (page 36).

Braces
------

Use ``braces``, not ``curly brackets``, to describe these symbols: { }.

When you need to distinguish between the opening and closing braces, use ``left brace``
and ``right brace``.

Brackets
--------

Use ``brackets``, not ``squarebrackets``, to describe these symbols: [].

Don’t use ``brackets`` when you mean ``angle brackets`` (< >).

Capitalization
--------------

Three styles of capitalization are available: sentence style, title style, and all caps.

- Sentence-style capitalization::

	This line provides an example of sentence-style capitalization.

- Title-style capitalization::

	This Line Provides an Example of Title-Style Capitalization.

- All caps::

	THIS LINE PROVIDES AN EXAMPLE OF ALL CAPS.

Don’t use all caps for emphasis.

Capitalization (Title Style)
----------------------------

Use title-style capitalization for book titles, part titles, chapter titles, section titles
(text heads), disc titles, running footers that use chapter titles, and cross-references to
such titles.

- References to specific book elements:
	In cross-references to a specific appendix or chapter, capitalize the word Appendix or
	Chapter (exception to The Chicago Manual of Style). When you refer to appendixes or
	chapters in general, don’t capitalize the word appendix or chapter::

		See Chapter 2, “QuickTime on the Internet.”
		See Appendix B for a list of specifications.
		See the appendix for specifications.

- References to untitled sections:
	In cross-references to sections that never take a title (glossary, index, table of
	contents, and so on), don’t capitalize the name of the section.
- What to capitalize:
	Follow these rules when you use title-style capitalization.

	Capitalize every word except:

		- Articles (``a, an, the``), unless an article is the first word or follows a colon
		- Coordinating conjunctions(``and, but, or, nor, for, yet`` and ``so``)
		- The word *to* in infinitives (``How to Install TYPO3 Flow``)
		- The word *as*, regardless of the part of speech
		- Words that always begin with a lower case letter, such as ``iPad``
		- Prepositions of four letters or fewer (``at, by, for, from, in, into, of, off, on,
		  onto, out, over, to, up`` and ``with``), except when the word is part of a verb phrase
		  or is used as another part of speech (such as an adverb, adjective, noun, or verb)::

			Starting Up the Computer
			Logging In to the Server
			Getting Started with Your MacBook Pro

Capitalize:

	- The first and last word, regardless of the part of speech::

		For New Mac OS X Users
		What the Finder Is For

	- The second word in a hyphenated compound::

		Correct: High-Level Events, 32-Bit Addressing
		Incorrect: High-level Events, 32-bit Addressing
		Exceptions: Built-in, Plug-in

	- The words ``Are, If, Is, It, Than, That`` and ``This``

Command Line
------------

Write as two separate words when referring to the noun and use the hypenated form ``command-line``
for and adjective.

Commas
------

Use a serial comma before ``and`` or ``or`` in a list of three or more items.

Correct: ``Apple sells MacBook Pro computers, the AirPort Extreme Card, and Final Cut Pro software.``

Incorrect: ``Apple sells MacBook Pro computers, the AirPort Extreme Card and Final Cut Pro software.``

Dash (em)
---------

Use the em dash (---) to set off a word or phrase that interrupts or changes the direction
of a sentence or to set off a lengthy list that would otherwise make the syntax of a sentence
confusing. Don’t overuse em dashes. If the text being set off does not come at the end of the
sentence, use an em dash both before it and after it::

	Setting just three edit points—the clip In point, the clip Out point, and the sequence In
	point—gives you total control of the edit that’s performed.

To generate an em dash in a reStructured text, use ``---``.
Close up the em dash with the word before it and the word after it. Consult your department’s
guidelines for instructions on handling em dashes in HTML.

dash (en)
---------

The en dash (--) is shorter than an em dash and longer than a hyphen. Use the en dash as
follows:

- Numbers in a range:
	Use an en dash between numbers that represent the endpoints of a continuous range::

		bits 3–17, 2003–2005

- Compound adjectives:
	Use an en dash between the elements of a compound adjective when one of those elements is
	itself two words::

		desktop interface–specific instructions

- Keyboard shortcuts using combination keystrokes:
	Use an en dash between key names in a combination keystroke when at least one of those
	names is two words or a hyphenated word::

		Command–Option–Up Arrow, Command–Shift–double-click See also key, keys.

- Minus sign:
	Use an en dash as a minus sign (except in code font, where you use a hyphen)::

		–1, –65,535

To generate an en dash in ReStructured Text, use ``--``. Close up the en
dash with the word (or number) before it and the word (or number) after it.

Kickstarter
-----------

A small application provided by the Kickstart paackage, which generates scaffolding for packages,
models, controllers and more.

