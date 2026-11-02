<?php

namespace Neos\Flow\Tests\Functional\Persistence\FixturesPHP8;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * Testing advanced properties of types:
 *
 * \Doctrine\DBAL\Types\Type::SIMPLE_ARRAY
 * \Doctrine\DBAL\Types\Type::JSON_ARRAY
 * \Doctrine\DBAL\Types\Type::DATETIME
 * \Doctrine\DBAL\Types\Type::DATETIMETZ
 * \Doctrine\DBAL\Types\Type::DATE
 * \Doctrine\DBAL\Types\Type::TIME
 * \Doctrine\DBAL\Types\Type::OBJECT
 */
#[Flow\Entity]
class ExtendedTypesEntity
{
    #[ORM\Column(type: 'object', nullable: true)]
    protected ?CommonObject $commonObject;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    protected ?array $simpleArray;

    #[ORM\Column(type: 'flow_json_array', nullable: true)]
    protected ?array $jsonArray;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $dateTime;

    #[ORM\Column(type: 'datetimetz', nullable: true)]
    protected ?\DateTime $dateTimeTz;

    #[ORM\Column(type: 'date', nullable: true)]
    protected ?\DateTime $date;

    #[ORM\Column(type: 'time', nullable: true)]
    protected ?\DateTime $time;

    #[ORM\Column(nullable: true)]
    protected ?\DateTimeImmutable $dateTimeImmutable;

    /**
     * This is possible for b/c - see #1673
     */
    #[ORM\Column(nullable: true)]
    protected \DateTimeInterface $dateTimeInterface;

    public function setTime(?\DateTime $time): self
    {
        $this->time = $time;
        return $this;
    }

    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    public function setDate(?\DateTime $date = null): self
    {
        $this->date = $date;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDateTimeTz(?\DateTime $dateTimeTz = null): self
    {
        $this->dateTimeTz = $dateTimeTz;
        return $this;
    }

    public function getDateTimeTz(): ?\DateTime
    {
        return $this->dateTimeTz;
    }

    public function setDateTime(?\DateTime $dateTime = null): self
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime;
    }

    public function setDateTimeImmutable(?\DateTimeImmutable $dateTime = null): self
    {
        $this->dateTimeImmutable = $dateTime;
        return $this;
    }

    public function getDateTimeImmutable(): ?\DateTimeImmutable
    {
        return $this->dateTimeImmutable;
    }

    public function setDateTimeInterface(?\DateTimeInterface $dateTime = null): self
    {
        $this->dateTimeInterface = $dateTime;
        return $this;
    }

    public function getDateTimeInterface(): ?\DateTimeInterface
    {
        return $this->dateTimeInterface;
    }

    public function setCommonObject(?CommonObject $commonObject = null): self
    {
        $this->commonObject = $commonObject;
        return $this;
    }

    public function getCommonObject(): ?CommonObject
    {
        return $this->commonObject;
    }

    public function setSimpleArray(?array $simpleArray = null): self
    {
        $this->simpleArray = $simpleArray;
        return $this;
    }

    public function getSimpleArray(): ?array
    {
        return $this->simpleArray;
    }

    public function setJsonArray(?array $jsonArray = null): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }

    public function getJsonArray(): ?array
    {
        return $this->jsonArray;
    }
}
