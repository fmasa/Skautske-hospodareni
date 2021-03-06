<?php

declare(strict_types=1);

namespace App\Forms;

use App\Bootstrap4FormRenderer;
use App\FormRenderer;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\CsrfProtection;

class BaseForm extends Form
{
    use CustomControlFactories;

    /** @var CsrfProtection */
    private $protection;

    public function __construct(bool $inline = false)
    {
        parent::__construct(null, null);
        $this->setRenderer(new FormRenderer($inline));
        $this->protection = parent::addProtection('Vypršela platnost formuláře, zkus to ještě jednou.');
    }

    public function useBootstrap4() : void
    {
        $this->setRenderer(new Bootstrap4FormRenderer());
    }

    /**
     * @deprecated CSRF protection is auto-enabled for all forms
     */
    public function getProtection() : CsrfProtection
    {
        return $this->protection;
    }
}
