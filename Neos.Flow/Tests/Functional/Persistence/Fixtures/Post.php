<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\Persistence\Fixtures;

/**
 * A sample entity for tests
 *
 * @Flow\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\InheritanceType("JOINED")
 */
class Post
{
    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var Image
     * @ORM\OneToOne
     */
    protected $image;

    /**
     * @var Image
     * @ORM\OneToOne
     */
    protected $thumbnail;

    /**
     * Yeah, only one comment allowed for a post ;-)
     * But that's the easiest option for our functional test.
     *
     * @var Comment
     * @ORM\OneToOne
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $comment;

    /**
     * @var Collection<Fixtures\Tag>
     * @ORM\OneToMany
     */
    protected $tags;

    /**
     * @var Collection<Fixtures\Post>
     * @ORM\ManyToMany
     * @ORM\JoinTable(inverseJoinColumns={@ORM\JoinColumn(name="related_post_id")})
     */
    protected $related;

    /**
     * @var TestValueObject
     * @ORM\ManyToOne
     */
    protected $author;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->related = new ArrayCollection();
    }

    /**
     * @return string
     * @ORM\PrePersist
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param Image $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param $comment
     * @return void
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return Comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);
    }

    /**
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return TestValueObject
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param TestValueObject $author
     */
    public function setAuthor(TestValueObject $author)
    {
        $this->author = $author;
    }
}
