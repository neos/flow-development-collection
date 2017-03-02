<?php
namespace Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * A test entity which is used to test Fluid forms in combination with
 * property mapping
 *
 * @Flow\Entity
 */
class Post
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var User
     * @ORM\ManyToOne(cascade={"all"})
     */
    protected $author;

    /**
     * @var boolean
     * @ORM\Column(nullable=true)
     */
    protected $private;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $category;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $subCategory;

    /**
     * @var Collection<Tag>
     * @ORM\ManyToMany
     * @ORM\JoinTable(inverseJoinColumns={@ORM\JoinColumn(unique=true)})
     */
    protected $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param User $author
     * @return void
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param boolean $private
     */
    public function setPrivate($private)
    {
        $this->private = $private;
    }

    /**
     * @return boolean
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->private ? 'Private!' : 'Public';
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getHasCategory()
    {
        return !empty($this->category);
    }

    /**
     * @param string $subCategory
     */
    public function setSubCategory($subCategory)
    {
        $this->subCategory = $subCategory;
    }

    /**
     * @return string
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);
    }

    /**
     * @return Collection<Tag>
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Collection<Tag> $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }
}
