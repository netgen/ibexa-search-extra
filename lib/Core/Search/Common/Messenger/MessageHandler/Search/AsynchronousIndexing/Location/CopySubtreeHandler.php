<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\AsynchronousIndexing\Location;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Location\CopySubtree;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\AsynchronousIndexing\SubtreeIndexer;

final class CopySubtreeHandler
{
    public function __construct(
        private readonly SubtreeIndexer $subtreeIndexer,
    ) {}

    public function __invoke(CopySubtree $message): void
    {
        $this->subtreeIndexer->indexSubtree($message->locationId);
    }
}
