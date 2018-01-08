<?php

namespace Model\Skautis\Auth;

use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp;
use Model\Auth\Resources\Event;
use Model\Auth\Resources\Unit;
use Skautis\Wsdl\WebServiceInterface;

final class SkautisAuthorizator implements IAuthorizator
{

    /** @var WebServiceInterface */
    private $userWebservice;

    private const RESOURCE_CLASS_TO_SKAUTIS_TABLE_MAP = [
        Event::class => "EV_EventGeneral",
        Unit::class => "OU_Unit",
        Camp::class => 'EV_EventCamp',
    ];


    public function __construct(WebServiceInterface $userWebservice)
    {
        $this->userWebservice = $userWebservice;
    }

    public function isAllowed(array $action, ?int $resourceId): bool
    {
        if(count($action) !== 2 || ! isset(self::RESOURCE_CLASS_TO_SKAUTIS_TABLE_MAP[$action[0]])) {
            throw new \InvalidArgumentException("Unknown action");
        }

        $skautisTable = self::RESOURCE_CLASS_TO_SKAUTIS_TABLE_MAP[$action[0]];

        foreach ($this->getAvailableActions($skautisTable, $resourceId) as $skautisAction) {
            if($skautisAction->ID === $action[1]) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @return \stdClass[]
     */
    private function getAvailableActions(string $skautisTable, ?int $id): array
    {
        try {
            $result = $this->userWebservice->ActionVerify([
                "ID" => $id,
                "ID_Table" => $skautisTable,
                "ID_Action" => NULL,
            ]);

            return is_array($result)
                ? $result
                : [];
        } catch (\Skautis\Wsdl\PermissionException $exc) {
            return [];
        }
    }

}
