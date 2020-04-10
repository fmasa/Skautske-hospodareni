<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services;

use Closure;
use Skautis\Wsdl\WebServiceInterface;

/**
 * WebService wrapper that obtains webservice instance from Skautis only when it's called for the first time.
 *
 * This makes it possible to delay Skautis initialization while login ID may not be correctly set during login.
 * Otherwise Skatuis object would be initialized without login ID and it cannot be changed later.
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
 */
class LazyWebService implements WebServiceInterface
{
    /** @var Closure(): WebServiceInterface */
    private Closure $factory;

    /** @var WebServiceInterface|null */
    private $webService;

    /**
     * @param Closure(): WebServiceInterface $factory
     */
    public function __construct(Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string               $functionName
     * @param array<string, mixed> $arguments
     *
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function call($functionName, array $arguments = [])
    {
        return $this->webServiceInstance()->call($functionName, $arguments);
    }

    /**
     * @param string               $functionName
     * @param array<string, mixed> $arguments
     *
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function __call($functionName, $arguments)
    {
        return $this->webServiceInstance()->__call($functionName, $arguments);
    }

    /**
     * @param string $eventName
     *
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function subscribe($eventName, callable $callback)
    {
        return $this->webServiceInstance()->subscribe($eventName, $callback);
    }

    private function webServiceInstance() : WebServiceInterface
    {
        if ($this->webService === null) {
            $this->webService = ($this->factory)();
        }

        return $this->webService;
    }
}
