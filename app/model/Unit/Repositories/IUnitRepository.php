<?php

declare(strict_types=1);

namespace Model\Unit\Repositories;

use Model\Unit\Unit;
use Model\Unit\UnitNotFound;
use stdClass;

interface IUnitRepository
{
    /**
     * @return Unit[]
     */
    public function findByParent(int $parentId) : array;

    /**
     * @throws UnitNotFound
     */
    public function find(int $id) : Unit;

    /**
     * @deprecated Use IUnitRepository::find()
     */
    public function findAsStdClass(int $id) : stdClass;
}
