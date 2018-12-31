<?php

declare(strict_types=1);

namespace Model;

use Model\Budget\Repositories\IPaymentRepository;
use Model\Participant\Participant;
use Model\Participant\Payment;
use Model\Participant\PaymentNotFound;
use Model\Budget\Repositories\IParticipantRepository;
use Model\Event\SkautisEventId;
use Model\Participant\PaymentFactory;
use Model\Participant\PaymentNofFound;
use Model\Participant\PragueParticipants;
use Model\Services\Language;
use Model\Skautis\Factory\ParticipantFactory;
use Model\Utils\MoneyFactory;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use function array_column;
use function array_combine;
use function array_diff_key;
use function array_key_exists;
use function array_reduce;
use function array_sum;
use function in_array;
use function is_array;
use function natcasesort;
use function preg_match;
use function sprintf;
use function stripos;
use function usort;

class ParticipantService extends MutableBaseService
{
    private const PRAGUE_SUPPORTABLE_AGE       = 18;
    private const PRAGUE_SUPPORTABLE_UPPER_AGE = 26;
    private const PRAGUE_UNIT_PREFIX           = 11;

    /** @var IPaymentRepository */
    private $repository;

    public function __construct(string $name, Skautis $skautIS, IPaymentRepository $repository)
    {
        parent::__construct($name, $skautIS);
        $this->repository = $repository;
    }

    /**
     * název pod kterým je uložena čáska ve skautISu
     */
    public const PAYMENT = 'Note';

    public function get(int $participantId, int $actionId) : Participant
    {
        $data = $this->skautis->event->{'Participant' . $this->typeName . 'Detail'}(['ID' => $participantId]);
        return ParticipantFactory::create($data, $this->getPayment($participantId, $actionId));
    }

    /**
     * vrací seznam účastníků
     * používá lokální úložiště
     *
     * @return mixed[]
     */
    public function getAll(int $ID_Event) : array
    {
        $eventId         = new SkautisEventId($ID_Event);
        $participantsSis = (array) $this->skautis->event->{'Participant' . $this->typeName . 'All'}(['ID_Event' . $this->typeName => $eventId->toInt()]);
        if (! is_array($participantsSis)) {//pokud je prázdná třída stdClass
            return [];
        }

        $participantPayments = $this->repository->findPaymentsByEvent($eventId);
        $participants        = [];
        /** @var \stdClass $p */
        foreach ($participantsSis as $p) {
            $payment              = array_key_exists($p->ID, $participantPayments) ? $participantPayments[$p->ID] : PaymentFactory::createDefault($p->ID, $eventId);
            $participants[$p->ID] = ParticipantFactory::create($p, $payment);
        }

        if ($this->type === 'camp') {
            foreach (array_diff_key($participantPayments, $participants) as $idForDelete) {
                $this->repository->deletePayment($participantPayments[$idForDelete]); //delete zaznam, protoze neexistuje k nemu ucastnik
            }
        }

        usort(
            $participants,
            function (Participant $one, Participant $two) : int {
                return Language::compare($one->getDisplayName(), $two->getDisplayName());
            }
        );

        return $participants;
    }

    /**
     * přidat účastníka k akci
     *
     * @throws WsdlException
     */
    public function add(int $ID, int $participantId) : bool
    {
        try {
            return (bool) $this->skautis->event->{'Participant' . $this->typeName . 'Insert'}(
                [
                'ID_Event' . $this->typeName => $ID,
                'ID_Person' => $participantId,
                ]
            );
        } catch (WsdlException $ex) {
            if (! preg_match('/Chyba validace \(Participant_PersonIsAllreadyParticipant(General)?\)/', $ex->getMessage())) {
                throw $ex;
            }
        }

        return false;
    }

    /**
     * vytvoří nového účastníka
     *
     * @param string[] $person
     */
    public function addNew(int $ID, array $person) : void
    {
        $newParticipantArr = $this->skautis->event->{'Participant' . $this->typeName . 'Insert'}(
            [
            'ID_Event' . $this->typeName => $ID,
            'Person' => [
                'FirstName' => $person['firstName'],
                'LastName' => $person['lastName'],
                'NickName' => $person['nick'],
                'Note' => '',
            ],
            ]
        );
        $this->personUpdate($newParticipantArr->ID_Person, $person);
    }

    /**
     * upravuje údaje zadané osoby
     *
     * @param mixed[] $data
     */
    public function personUpdate(int $pid, array $data) : void
    {
        $this->skautis->org->PersonUpdateBasic(
            [
            'ID' => $pid,
            'FirstName' => $data['firstName'] ?? null,
            'LastName' => $data['lastName'] ?? null,
            'IdentificationCode' => null,
            'Birthday' => $data['Birthday'] ?? null,
            'Street' => $data['street'] ?? null,
            'City' => $data['city'] ?? null,
            'Postcode' => $data['postcode'] ?? null,
            ]
        );
    }

    /**
     * @param mixed[] $arr
     */
    public function update(int $participantId, int $actionId, array $arr) : void
    {
        if ($this->typeName === 'Camp') {
            if (in_array('days', $arr)) {
                $sisData = [
                    'ID' => $participantId,
                    'Real' => true,
                    'Days' => $arr['days'],
                ];
                $this->skautis->event->{'Participant' . $this->typeName . 'Update'}($sisData, 'participant' . $this->typeName);
                unset($arr['days']);
                if (empty($arr)) {
                    return;
                }
            }

            //@todo: check actionId privileges
            $payment = $this->getPayment($participantId, $actionId);

            foreach ($arr as $key => $value) {
                switch ($key) {
                    case 'payment':
                        $payment->setPayment(MoneyFactory::fromFloat((float) $value));
                        break;
                    case 'repayment':
                        $payment->setRepayment(MoneyFactory::fromFloat((float) $value));
                        break;
                    case 'isAccount':
                        $payment->setAccount($value);
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf("Camp participant hasn't attribute '%s'", $key));
                }
            }
            $this->repository->savePayment($payment);
        } else {
            $origin  = $this->get($participantId, $actionId);
            $sisData = [
                'ID' => $participantId,
                'Real' => true,
                'Days' => $origin->getDays(),
                self::PAYMENT => $origin->getPayment()->getAmount(),
            ];
            foreach ($arr as $key => $value) {
                switch ($key) {
                    case 'days':
                        $sisData['Days'] = $value;
                        break;
                    case 'payment':
                        $sisData[self::PAYMENT] = $value;
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf("General event participant hasn't attribute '%s'", $key));
                        break;
                }
            }
            $this->skautis->event->{'Participant' . $this->typeName . 'Update'}($sisData, 'participant' . $this->typeName);
        }
    }

    public function removeParticipant(int $participantId) : void
    {
        try {
            $this->repository->deletePayment($this->repository->findPayment($participantId));
        } catch (PaymentNofFound $exc) {
        }
        $this->skautis->event->{'Participant' . $this->typeName . 'Delete'}(['ID' => $participantId, 'DeletePerson' => false]);
    }

    public function getTotalPayment(int $eventId) : float
    {
        return (float) array_reduce(
            $this->getAll($eventId),
            function ($res, $v) {
                return isset($v->{ParticipantService::PAYMENT}) ? $res + $v->{ParticipantService::PAYMENT} : $res;
            }
        );
    }

    /**
     * vrací počet osobodní na dané akci
     *
     * @param int|int[] $eventIdOrParticipants
     */
    public function getPersonsDays($eventIdOrParticipants) : int
    {
        if (is_array($eventIdOrParticipants)) {
            $participants = $eventIdOrParticipants;
        } else {
            $participants = $this->getAll($eventIdOrParticipants);
        }

        return array_sum(
            array_column($participants, 'Days')
        );
    }

    /**
     * @return mixed[]
     */
    public function getEventStatistic(int $eventId) : array
    {
        $skautisData = $this->skautis->event->{'EventStatisticAllEventGeneral'}(['ID_EventGeneral' => $eventId]);

        return array_combine(
            array_column($skautisData, 'ID_ParticipantCategory'),
            $skautisData
        );
    }

    /**
     * @param string $isAccount 'Y' or 'N'
     */
    public function getCampTotalPayment(int $campId, string $category, string $isAccount) : float
    {
        $res = 0;
        foreach ($this->getAll($campId) as $p) {
            //pokud se alespon v jednom neshodují, tak pokracujte
            if (($category === 'adult' xor preg_match('/^Dospěl/', $p->Category))
                || ($isAccount === 'Y' xor $p->isAccount === 'Y')
            ) {
                continue;
            }
            $res += $p->payment;
        }
        return $res;
    }

    /**
     * @return mixed[]
     */
    public function getPotencialCampParticipants(int $eventId) : array
    {
        $res = [];
        foreach ($this->skautis->org->{'PersonAllEventCampMulti'}(['ID_EventCamp' => $eventId]) as $p) {
            $res[$p->ID] = $p->DisplayName;
        }
        natcasesort($res);
        return $res;
    }

    /**
     * @param \stdClass|ArrayHash $person
     */
    protected function setPersonName(&$person) : void
    {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $person->Person, $matches);
        $person->LastName  = $matches['last'];
        $person->FirstName = $matches['first'];
        $person->NickName  = $matches['nick'] ?? null;
    }

    public function countPragueParticipants(\stdClass $event) : ?PragueParticipants
    {
        if (! Strings::startsWith($event->RegistrationNumber, self::PRAGUE_UNIT_PREFIX)) {
            return null;
        }

        $eventStartDate    = new \DateTime($event->StartDate);
        $participants      = $this->getAll($event->ID);
        $under18           = 0;
        $between18and26    = 0;
        $personDaysUnder26 = 0;
        $citizensCount     = 0;
        foreach ($participants as $p) {
            if (stripos($p->City, 'Praha') === false) {
                continue;
            }
            $citizensCount += 1;

            if ($p->Birthday === null) {
                continue;
            }
            $ageInYears = $eventStartDate->diff(new \DateTime($p->Birthday))->format('%Y');

            if ($ageInYears <= self::PRAGUE_SUPPORTABLE_AGE) {
                $under18 += 1;
            }

            if (self::PRAGUE_SUPPORTABLE_AGE < $ageInYears && $ageInYears <= self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                $between18and26 += 1;
            }

            if ($ageInYears > self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                continue;
            }

            $personDaysUnder26 += $p->Days;
        }
        return new PragueParticipants($under18, $between18and26, $personDaysUnder26, $citizensCount);
    }

    private function getPayment(int $participantId, int $actionId) : Payment
    {
        try {
            $payment = $this->repository->findPayment($participantId);
        } catch (PaymentNofFound $exc) {
            $payment = PaymentFactory::createDefault($participantId, new SkautisEventId($actionId));
        }
        return $payment;
    }
}
