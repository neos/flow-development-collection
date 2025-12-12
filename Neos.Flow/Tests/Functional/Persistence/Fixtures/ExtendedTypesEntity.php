<?php
declare(strict_types=1);

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

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Testing advanced properties of types:
 *
 * \Doctrine\DBAL\Types\Type::SIMPLE_ARRAY
 * \Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType
 * \Doctrine\DBAL\Types\Type::DATETIME
 * \Doctrine\DBAL\Types\Type::DATETIMETZ
 * \Doctrine\DBAL\Types\Type::DATE
 * \Doctrine\DBAL\Types\Type::TIME
 *
 * @Flow\Entity
 */
class ExtendedTypesEntity
{
    /**
     * @var array
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected $simpleArray;

    /**
     * @var array
     * @ORM\Column(type="flow_json_array", nullable=true)
     */
    protected $jsonArray;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateTime;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    protected $dateTimeTz;

    /**
     * @var \DateTime
     * @ORM\Column(type="date", nullable=true)
     */
    protected $date;

    /**
     * @var \DateTime
     * @ORM\Column(type="time", nullable=true)
     */
    protected $time;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(nullable=true)
     */
    protected $dateTimeImmutable;

    /**
     * This is possible for b/c - see #1673
     * @var \DateTimeInterface
     * @ORM\Column(nullable=true)
     */
    protected $dateTimeInterface;

    /**
     * @param \DateTime $time
     * @return $this
     */
    public function setTime(\DateTime $time): self
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return ?\DateTime
     */
    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    /**
     * @param \DateTime|null $date
     * @return $this
     */
    public function setDate(?\DateTime $date = null): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return ?\DateTime
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime|null $dateTimeTz
     * @return $this
     */
    public function setDateTimeTz(?\DateTime $dateTimeTz = null): self
    {
        $this->dateTimeTz = $dateTimeTz;
        return $this;
    }

    /**
     * @return ?\DateTime
     */
    public function getDateTimeTz(): ?\DateTime
    {
        return $this->dateTimeTz;
    }

    /**
     * @param \DateTime|null $dateTime
     * @return $this
     */
    public function setDateTime(?\DateTime $dateTime = null): self
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    /**
     * @return ?\DateTime
     */
    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTimeImmutable|null $dateTime
     * @return $this
     */
    public function setDateTimeImmutable(?\DateTimeImmutable $dateTime = null): self
    {
        $this->dateTimeImmutable = $dateTime;
        return $this;
    }

    /**
     * @return ?\DateTimeImmutable
     */
    public function getDateTimeImmutable(): ?\DateTimeImmutable
    {
        return $this->dateTimeImmutable;
    }

    /**
     * @param \DateTimeInterface|null $dateTime
     * @return $this
     */
    public function setDateTimeInterface(?\DateTimeInterface $dateTime = null): self
    {
        $this->dateTimeInterface = $dateTime;
        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getDateTimeInterface(): ?\DateTimeInterface
    {
        return $this->dateTimeInterface;
    }

    /**
     * @param array|null $simpleArray
     * @return $this
     */
    public function setSimpleArray(?array $simpleArray = null): self
    {
        $this->simpleArray = $simpleArray;
        return $this;
    }

    /**
     * @return ?array
     */
    public function getSimpleArray(): ?array
    {
        return $this->simpleArray;
    }

    /**
     * @param array|null $jsonArray
     * @return $this
     */
    public function setJsonArray(?array $jsonArray = null): self
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }

    /**
     * @return ?array
     */
    public function getJsonArray(): ?array
    {
        return $this->jsonArray;
    }
}
