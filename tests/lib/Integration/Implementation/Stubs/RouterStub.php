<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\Implementation\Stubs;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class RouterStub implements RouterInterface
{
    public function setContext(RequestContext $context)
    {
        // TODO: Implement setContext() method.
    }

    public function getContext()
    {
        // TODO: Implement getContext() method.
    }

    public function getRouteCollection()
    {
        // TODO: Implement getRouteCollection() method.
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH)
    {
        // TODO: Implement generate() method.
    }

    public function match(string $pathinfo)
    {
        // TODO: Implement match() method.
    }
}