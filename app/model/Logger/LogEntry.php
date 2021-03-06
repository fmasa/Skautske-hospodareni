<?php

declare(strict_types=1);

namespace Model\Logger;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Logger\Log\Type;

/**
 * @ORM\Entity()
 * @ORM\Table(name="log")
 */
class LogEntry
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="unitId", options={"unsigned"=true})
     *
     * @var int
     */
    private $unitId;

    /**
     * @ORM\Column(type="datetime_immutable")
     *
     * @var DateTimeImmutable
     */
    private $date;

    /**
     * @ORM\Column(type="integer", name="userId", options={"unsigned"=true})
     *
     * @var int
     */
    private $userId;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="string_enum", name="type")
     *
     * @var Type
     * @Enum(class=Type::class)
     */
    private $type;

    /**
     * @ORM\Column(type="integer", nullable=true, name="typeId", options={"unsigned"=true})
     *
     * @var int|NULL
     */
    private $typeId;

    public function __construct(
        int $unitId,
        int $userId,
        string $desc,
        Type $type,
        ?int $typeId,
        DateTimeImmutable $at
    ) {
        $this->unitId      = $unitId;
        $this->date        = $at;
        $this->userId      = $userId;
        $this->description = $desc;
        $this->type        = $type;
        $this->typeId      = $typeId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getDate() : DateTimeImmutable
    {
        return $this->date;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getType() : Type
    {
        return $this->type;
    }

    public function getTypeId() : ?int
    {
        return $this->typeId;
    }
}
