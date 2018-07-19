<?php

declare(strict_types=1);

namespace Model;

use Dibi\Row;

class ParticipantTable extends BaseTable
{
    /**
     * @return Row|false
     */
    public function get(int $participantId)
    {
        return $this->connection->fetch('SELECT * FROM  [' . self::TABLE_CAMP_PARTICIPANT . '] WHERE participantId=%i LIMIT 1', $participantId);
    }

    /**
     * seznam všech záznamů k dané akci
     *
     * @return mixed[]
     */
    public function getCampLocalDetails(int $skautiEventId) : array
    {
        $participants = $this->connection->fetchAll('SELECT participantId, payment, repayment, isAccount FROM [' . self::TABLE_CAMP_PARTICIPANT . '] WHERE actionId=%i', $skautiEventId);
        $result       = [];

        foreach ($participants as $participant) {
            $result[$participant->participantId] = $participant;
        }

        return $result;
    }

    public function deleteLocalDetail(int $participantId) : void
    {
        $this->connection->query('DELETE FROM [' . self::TABLE_CAMP_PARTICIPANT . '] WHERE participantId =%i', $participantId);
    }

    /**
     * @param mixed[] $updateData
     */
    public function update(int $participantId, array $updateData) : void
    {
        $ins                  = $updateData;
        $ins['participantId'] = $participantId;
        unset($updateData['actionId']); //to se neaktualizuje
        $this->connection->query(
            'INSERT INTO [' . self::TABLE_CAMP_PARTICIPANT . ']',
            $ins,
            '
            ON DUPLICATE KEY 
            UPDATE %a',
            $updateData
        );
    }
}
