<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Command;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Model\Travel\Command;
use Model\Travel\Commands\Command\DuplicateTravel;
use Model\Travel\Passenger;
use Money\Money;
use function end;

class DuplicateTravelHandlerTest extends Unit
{
    public function testTransportTravelIsDuplicated() : void
    {
        $passenger = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose   = 'Cesta na střediskovku';
        $command   = new Command(1, null, $passenger, $purpose, 'Brno', '', Money::CZK(0), Money::CZK(0), '', null, [], '');

        $command->addTransportTravel(Money::CZK(100), new Command\TravelDetails(new Date('now'), 'a', 'Praha', 'Brno'));

        $travels = $command->getTravels();
        $travel  = end($travels);

        $this->commandBus->handle(new DuplicateTravel($command->getId(), $travel->getId()));

        $travels          = $command->getTravels();
        $duplicatedTravel = end($travels);

        $travelDetails           = $travel->getDetails();
        $duplicatedTravelDetails = $duplicatedTravel->getDetails();

        $this->assertNotEquals($travel->getId(), $duplicatedTravel->getId());
        $this->assertEquals($travelDetails->getDate(), $duplicatedTravelDetails->getDate());
        $this->assertEquals($travelDetails->getTransportType(), $duplicatedTravelDetails->getTransportType());
        $this->assertEquals($travelDetails->getStartPlace(), $duplicatedTravelDetails->getStartPlace());
        $this->assertEquals($travelDetails->getEndPlace(), $duplicatedTravelDetails->getEndPlace());
    }

    public function testVehicleTravelIsDuplicated() : void
    {
        $passenger = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose   = 'Cesta na střediskovku';
        $command   = new Command(1, null, $passenger, $purpose, 'Brno', '', Money::CZK(3120), Money::CZK(500), '', null, [], '');

        $command->addVehicleTravel(123, new Command\TravelDetails(new Date('now'), 'auv', 'Praha', 'Brno'));

        $travels = $command->getTravels();
        $travel  = end($travels);

        $this->commandBus->handle(new DuplicateTravel($command->getId(), $travel->getId()));

        $travels          = $command->getTravels();
        $duplicatedTravel = end($travels);

        $travelDetails           = $travel->getDetails();
        $duplicatedTravelDetails = $duplicatedTravel->getDetails();

        $this->assertNotEquals($travel->getId(), $duplicatedTravel->getId());
        $this->assertEquals($travelDetails->getDate(), $duplicatedTravelDetails->getDate());
        $this->assertEquals($travelDetails->getTransportType(), $duplicatedTravelDetails->getTransportType());
        $this->assertEquals($travelDetails->getStartPlace(), $duplicatedTravelDetails->getStartPlace());
        $this->assertEquals($travelDetails->getEndPlace(), $duplicatedTravelDetails->getEndPlace());
    }
}
