<?php

namespace Model\Event\Handlers\Event;

use Model\Event\Commands\Event\ActivateStatistics;
use Skautis\Skautis;

class ActivateStatisticsHandler
{

    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function handle(ActivateStatistics $command): void
    {
        $this->skautis->event->eventGeneralUpdateStatisticAutoComputed([
            'ID' => $command->getEventId(),
            "IsStatisticAutoComputed" => TRUE
        ], "eventGeneral");
    }

}
