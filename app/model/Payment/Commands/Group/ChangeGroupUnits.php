<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Group;

final class ChangeGroupUnits
{
    /** @var int */
    private $groupId;

    /** @var int[] */
    private $unitIds;

    /**
     * @param int[] $unitIds
     */
    public function __construct(int $groupId, array $unitIds)
    {
        $this->groupId = $groupId;
        $this->unitIds = $unitIds;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }

    /**
     * @return int[]
     */
    public function getUnitIds() : array
    {
        return $this->unitIds;
    }
}
