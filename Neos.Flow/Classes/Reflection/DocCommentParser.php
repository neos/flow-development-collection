<?php
namespace Neos\Flow\Reflection;

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

/**
 * A little parser which creates tag objects from doc comments
 *
 * @Flow\Scope("singleton")
 */
class DocCommentParser
{
    /**
     * The description as found in the doc comment
     * @var string
     */
    protected $description = '';

    /**
     * An array of tag names and their values (multiple values are possible)
     * @var array
     */
    protected $tags = [];

    /**
     * Parses the given doc comment and saves the result (description and
     * tags) in the parser's object. They can be retrieved by the
     * getTags() getTagValues() and getDescription() methods.
     *
     * @param string $docComment A doc comment as returned by the reflection getDocComment() method
     * @return void
     */
    public function parseDocComment($docComment)
    {
        $this->description = '';
        $this->tags = [];

        $lines = explode(chr(10), $docComment);
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\*\/$/', '', $line));
            if ($line !== '' && strpos($line, '* @') !== false) {
                $this->parseTag(substr($line, strpos($line, '@')));
            } elseif (count($this->tags) === 0) {
                $this->description .= preg_replace('/\s*\\/?[\\\\*]*\s?(.*)$/', '$1', $line) . chr(10);
            }
        }
        $this->description = trim($this->description);
    }

    /**
     * Returns the tags which have been previously parsed
     *
     * @return array Array of tag names and their (multiple) values
     */
    public function getTagsValues()
    {
        return $this->tags;
    }

    /**
     * Returns the values of the specified tag. The doc comment
     * must be parsed with parseDocComment() before tags are
     * available.
     *
     * @param string $tagName The tag name to retrieve the values for
     * @return array The tag's values
     * @throws Exception
     */
    public function getTagValues($tagName)
    {
        if (!$this->isTaggedWith($tagName)) {
            throw new Exception('Tag "' . $tagName . '" does not exist.', 1169128255);
        }
        return $this->tags[$tagName];
    }

    /**
     * Checks if a tag with the given name exists
     *
     * @param string $tagName The tag name to check for
     * @return boolean true the tag exists, otherwise false
     */
    public function isTaggedWith($tagName)
    {
        return (isset($this->tags[$tagName]));
    }

    /**
     * Returns the description which has been previously parsed
     *
     * @return string The description which has been parsed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Parses a line of a doc comment for a tag and its value.
     * The result is stored in the internal tags array.
     *
     * @param string $line A line of a doc comment which starts with an @-sign
     * @return void
     */
    protected function parseTag($line)
    {
        $tagAndValue = [];
        if (preg_match('/@[A-Za-z0-9\\\\]+\\\\([A-Za-z0-9]+)(?:\\((.*)\\))?$/', $line, $tagAndValue) === 0) {
            $tagAndValue = preg_split('/\s/', $line, 2);
        } else {
            array_shift($tagAndValue);
        }
        $tag = strtolower(trim($tagAndValue[0], '@'));
        if (count($tagAndValue) > 1) {
            $this->tags[$tag][] = trim($tagAndValue[1], ' "');
        } else {
            $this->tags[$tag] = [];
        }
    }
}
