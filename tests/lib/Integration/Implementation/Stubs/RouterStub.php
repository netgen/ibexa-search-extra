<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Tests\Integration\Implementation\Stubs;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use RuntimeException;


class RouterStub implements RouterInterface
{
    public function setContext(RequestContext $context)
    {
        throw new RuntimeException("Not implemented");
    }

    public function getContext()
    {
        throw new RuntimeException("Not implemented");
    }

    public function getRouteCollection()
    {
        throw new RuntimeException("Not implemented");
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH)
    {
        throw new RuntimeException("Not implemented");
    }

    public function match(string $pathinfo)
    {
        throw new RuntimeException("Not implemented");
    }
}