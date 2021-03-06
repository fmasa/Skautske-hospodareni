<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\Forms\BaseForm;
use Model\MailService;
use Model\Payment\Commands\CreateMailCredentials;
use Model\Payment\Commands\RemoveMailCredentials;
use Model\Payment\EmailNotSet;
use Model\Payment\MailCredentials\MailProtocol;
use Nette\Application\UI\Form;
use Nette\Mail\SmtpException;

class MailPresenter extends BasePresenter
{
    /** @var MailService */
    private $model;

    public function __construct(MailService $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function renderDefault(int $unitId) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění přistupovat ke správě emailů', 'danger');
            $this->redirect('GroupList:');
        }
        $this->template->setParameters([
            'list'          => $this->model->getAll($this->getEditableUnitIds()),
            'editableUnits' => $this->getEditableUnits(),
        ]);
    }

    public function handleEdit(int $id) : void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Nemáte oprávnění měnit smtp', 'danger');
        $this->redirect('GroupList:');
    }

    public function handleRemove(int $id) : void
    {
        $mail = $this->model->get($id);

        if (! $this->isEditable || $mail->getUnitId() !== $this->unitId->toInt()) {
            $this->flashMessage('Nemáte oprávnění mazat smtp', 'danger');
            $this->redirect('GroupList:');
        }

        $this->commandBus->handle(new RemoveMailCredentials($id));
    }

    protected function createComponentFormCreate() : Form
    {
        $form = new BaseForm();
        $form->addText('host', 'Host')
            ->addRule(Form::FILLED, 'Musíte vyplnit pole host.')
            ->getControlPrototype()->placeholder('např. smtp.gmail.com');
        $form->addText('username', 'Uživatelské jméno')
            ->addRule(Form::FILLED, 'Musíte vyplnit uživatelské jméno.')
            ->getControlPrototype()->placeholder('např. platby@stredisko.cz');
        $form->addText('password', 'Heslo')
            ->addRule(Form::FILLED, 'Musíte vyplnit heslo.');
        $form->addSelect(
            'secure',
            'Zabezpečení',
            [
                MailProtocol::SSL => 'ssl',
                MailProtocol::TLS => 'tls',
            ]
        );
        $form->addText('sender', 'Email odesílatele')
            ->setRequired('Musíte vyplnit email odesílatele')
            ->addRule($form::EMAIL, 'Email odesílatele není platná emailová adresa')
            ->getControlPrototype()->placeholder('např. platby@stredisko.cz');
        $form->addSubmit('send', 'Založit')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formCreateSubmitted($form);
        };

        return $form;
    }

    private function formCreateSubmitted(Form $form) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění přidávat smtp', 'danger');
            $this->redirect('GroupList:');
        }
        $v = $form->getValues();

        $userId = $this->getUser()->getId();
        try {
            $this->commandBus->handle(
                new CreateMailCredentials(
                    $this->unitId->toInt(),
                    $v->host,
                    $v->username,
                    $v->password,
                    MailProtocol::get($v->secure),
                    $v->sender,
                    $userId
                )
            );

            $this->flashMessage('SMTP účet byl přidán');
        } catch (SmtpException $e) {
            $this->flashMessage('K SMTP účtu se nepodařilo připojit (' . $e->getMessage() . ')', 'danger');
        } catch (EmailNotSet $e) {
            $this->flashMessage('Nemáte nastavený email ve skautisu, na který by se odeslal testovací email!');
        }

        $this->redirect('this');
    }
}
