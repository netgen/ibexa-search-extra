<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\AsynchronousIndexing\Location;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Location\UnhideLocation;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\AsynchronousIndexing\SubtreeIndexer;

final class UnhideLocationHandler
{
    public function __construct(
        private readonly SubtreeIndexer $subtreeIndexer,
    ) {}

    public function __invoke(UnhideLocation $message): void
    {
        $this->subtreeIndexer->indexSubtree($message->locationId);
    }
}
