<?php

declare(strict_types=1);

namespace Model\Payment\Payment;

use Doctrine\ORM\Mapping as ORM;
use Model\Bank\Fio\Transaction as FioTransaction;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 *
 * @property-read int $id
 * @property-read string|NULL $bankAccount
 * @property-read string $payer
 * @property-read string|NULL $note
 */
class Transaction
{
    use SmartObject;

    /**
     * @ORM\Column(type="integer", nullable=true, name="transactionId", options={"unsigned"=true})
     *
     * @var int @todo start using string as does FIO
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, nullable=true, name="paidFrom")
     *
     * @var string
     */
    private $bankAccount;

    /**
     * @ORM\Column(type="string", nullable=true, name="transaction_payer")
     *
     * @var string
     */
    private $payer;

    /**
     * @ORM\Column(type="string", nullable=true, name="transaction_note")
     *
     * @var string|NULL
     */
    private $note;

    public function __construct(int $id, string $bankAccount, string $payer, ?string $note)
    {
        $this->id          = $id;
        $this->bankAccount = $bankAccount;
        $this->payer       = $payer;
        $this->note        = $note;
    }

    public static function fromFioTransaction(FioTransaction $transaction) : self
    {
        return new self(
            (int) $transaction->getId(),
            $transaction->getBankAccount(),
            $transaction->getName(),
            $transaction->getNote()
        );
    }

    public function getId() : int
    {
        return $this->id;
    }

    /**
     * TODO: fix some payment transactions in database, that have NULL bank accounts
     */
    public function getBankAccount() : ?string
    {
        return $this->bankAccount;
    }

    public function getPayer() : ?string
    {
        return $this->payer;
    }

    public function getNote() : ?string
    {
        return $this->note;
    }

    public function equals(self $other) : bool
    {
        return $other->id === $this->id
            && $other->bankAccount === $this->bankAccount
            && $other->note === $this->note
            && $other->payer === $this->payer;
    }
}
