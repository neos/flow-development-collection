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
    public function setTime(\DateTime $time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param \DateTime $date
     * @return $this
     */
    public function setDate(\DateTime $date = null)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $dateTimeTz
     * @return $this
     */
    public function setDateTimeTz(\DateTime $dateTimeTz = null)
    {
        $this->dateTimeTz = $dateTimeTz;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateTimeTz()
    {
        return $this->dateTimeTz;
    }

    /**
     * @param \DateTime $dateTime
     * @return $this
     */
    public function setDateTime(\DateTime $dateTime = null)
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTimeImmutable $dateTime
     * @return $this
     */
    public function setDateTimeImmutable(\DateTimeImmutable $dateTime = null)
    {
        $this->dateTimeImmutable = $dateTime;
        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDateTimeImmutable()
    {
        return $this->dateTimeImmutable;
    }

    /**
     * @param \DateTimeInterface $dateTime
     * @return $this
     */
    public function setDateTimeInterface(\DateTimeInterface $dateTime = null)
    {
        $this->dateTimeInterface = $dateTime;
        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateTimeInterface()
    {
        return $this->dateTimeInterface;
    }

    /**
     * @param array $simpleArray
     * @return $this
     */
    public function setSimpleArray(array $simpleArray = null)
    {
        $this->simpleArray = $simpleArray;
        return $this;
    }

    /**
     * @return array
     */
    public function getSimpleArray()
    {
        return $this->simpleArray;
    }

    /**
     * @param array $jsonArray
     * @return $this
     */
    public function setJsonArray(array $jsonArray = null)
    {
        $this->jsonArray = $jsonArray;
        return $this;
    }

    /**
     * @return array
     */
    public function getJsonArray()
    {
        return $this->jsonArray;
    }
}
