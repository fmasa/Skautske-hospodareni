<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\DTO\Payment\GroupEmail;
use Model\Payment\GroupNotFound;
use Model\Payment\ReadModel\Queries\GroupEmailQuery;
use Model\Payment\Repositories\IGroupRepository;

final class GroupEmailQueryHandler
{
    /** @var IGroupRepository */
    private $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function __invoke(GroupEmailQuery $query) : ?GroupEmail
    {
        try {
            $group    = $this->groups->find($query->getGroupId());
            $template = $group->getEmailTemplate($query->getEmailType());

            if ($template === null) {
                return null;
            }

            return new GroupEmail($template, $group->isEmailEnabled($query->getEmailType()));
        } catch (GroupNotFound $e) {
            return null;
        }
    }
}
