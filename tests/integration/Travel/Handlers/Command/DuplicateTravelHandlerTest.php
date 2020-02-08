<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Command;

use Cake\Chronos\Date;
use CommandHandlerTest;
use Model\Travel\Command;
use Model\Travel\Command\Travel;
use Model\Travel\Commands\Command\DuplicateTravel;
use Model\Travel\Passenger;
use Model\Travel\Repositories\ICommandRepository;
use Money\Money;

class DuplicateTravelHandlerTest extends CommandHandlerTest
{
    /** @var ICommandRepository */
    private $commands;

    public function _before() : void
    {
        $this->tester->useConfigFiles(['Travel/Handlers/Command/DuplicateTravelHandlerTest.neon']);
        $this->commands = $this->tester->grabService(ICommandRepository::class);
        parent::_before();
    }

    /**
     * @return string[]
     */
    public function getTestedAggregateRoots() : array
    {
        return [Travel::class];
    }

    public function testTransportTravelIsDuplicated() : void
    {
        $purpose = 'Cesta na střediskovku';
        $command = new Command(1, null, null, $purpose, 'Brno', '', null, null, '', null, [], '');

        $command->addTransportTravel(Money::CZK(100), new Command\TravelDetails(new Date('now'), 'letadlo', 'Praha', 'Brno'));
        $this->commands->save($command);

        $travel = end($command->getTravels());

        $this->commandBus->handle(new DuplicateTravel($command->getId(), $travel->getId()));

        $duplicatedTravel = end($command->getTravels());

        $travelDetails = $travel->getDetails();
        $duplicatedTravelDetails = $duplicatedTravel->getDetails();

        $this->assertEquals($travelDetails->getDate(), $duplicatedTravelDetails->getDate());
        $this->assertEquals($travelDetails->getTransportType(), $duplicatedTravelDetails->getTransportType());
        $this->assertEquals($travelDetails->getStartPlace(), $duplicatedTravelDetails->getStartPlace());
        $this->assertEquals($travelDetails->getEndPlace(), $duplicatedTravelDetails->getEndPlace());
    }

    public function testVehicleTravelIsDuplicated() : void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive('getId')->andReturn(6);
        $driver  = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose = 'Cesta na střediskovku';
        $command = new Command(2, $vehicle, $driver, $purpose, 'Brno', '', Money::CZK(3120), Money::CZK(500), '', null, [], '');

        $command->addVehicleTravel(123, new Command\TravelDetails(new Date('now'), 'auto vlastní', 'Praha', 'Brno'));
        $this->commands->save($command);

        $travel = end($command->getTravels());

        $this->commandBus->handle(new DuplicateTravel($command->getId(), $travel->getId()));

        $duplicatedTravel = end($command->getTravels());

        $travelDetails = $travel->getDetails();
        $duplicatedTravelDetails = $duplicatedTravel->getDetails();

        $this->assertEquals($travelDetails->getDate(), $duplicatedTravelDetails->getDate());
        $this->assertEquals($travelDetails->getTransportType(), $duplicatedTravelDetails->getTransportType());
        $this->assertEquals($travelDetails->getStartPlace(), $duplicatedTravelDetails->getStartPlace());
        $this->assertEquals($travelDetails->getEndPlace(), $duplicatedTravelDetails->getEndPlace());
    }
}
