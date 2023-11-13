<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\User;

final class BeforeUnAssignUserFromUserGroup
{
    public function __construct(
        public readonly int $contentId,
    ) {}
}
