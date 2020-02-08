<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Command;

use Model\Travel\Commands\Command\DuplicateTravel;
use Model\Travel\Repositories\ICommandRepository;

final class DuplicateTravelHandler
{
    /** @var ICommandRepository */
    private $commands;

    public function __construct(ICommandRepository $commands)
    {
        $this->commands = $commands;
    }

    public function __invoke(DuplicateTravel $command) : void
    {
        $travelCommand = $this->commands->find($command->getCommandId());
        $travelCommand->duplicateTravel($command->getTravelId());
        $this->commands->save($travelCommand);
    }
}
