<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures\Attributes;

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
use Neos\Flow\Tests\Functional\Persistence\Fixtures\Attributes as Fixtures;

#[Flow\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType("JOINED")]
class Post
{
    protected string $title = '';

    #[ORM\OneToOne(targetEntity: Image::class)]
    protected Image $image;

    #[ORM\OneToOne(targetEntity: Image::class)]
    protected Image $thumbnail;

    /**
     * Yeah, only one comment allowed for a post ;-)
     * But that's the easiest option for our functional test.
     */
    #[ORM\OneToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected Comment $comment;

    #[ORM\OneToMany(targetEntity: Fixtures\Tag::class)]
    protected Collection $tags;

    /**
     * This attribute only works with php 8.1 or greater
     * #[ORM\JoinTable(inverseJoinColumns: [new ORM\JoinColumn(name: "related_post_id")])]
     *
     * @ORM\JoinTable(inverseJoinColumns={@ORM\JoinColumn(name="related_post_id")})
     */
    #[ORM\ManyToMany(targetEntity: Fixtures\Post::class)]
    protected Collection $related;

    #[ORM\ManyToOne(targetEntity: TestValueObject::class)]
    protected TestValueObject $author;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->related = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function setComment(Comment $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function addTag(Tag $tag): void
    {
        $this->tags->add($tag);
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function getAuthor(): TestValueObject
    {
        return $this->author;
    }

    public function setAuthor(TestValueObject $author): void
    {
        $this->author = $author;
    }
}
