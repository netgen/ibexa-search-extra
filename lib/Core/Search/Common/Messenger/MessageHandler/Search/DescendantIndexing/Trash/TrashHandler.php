<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\Trash;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\AncestorIndexer;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Trash\Trash;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function sprintf;

final class TrashHandler
{
    public function __construct(
        private readonly LocationHandler $locationHandler,
        private readonly AncestorIndexer $ancestorIndexer,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(Trash $message): void
    {
        try {
            $location = $this->locationHandler->load(
                $message->parentLocationId,
            );
        } catch (NotFoundException) {
            $this->logger->info(
                sprintf(
                    '%s: Location #%d is gone, aborting',
                    $this::class,
                    $message->parentLocationId,
                ),
            );

            return;
        }

        if ($this->isRootLocation($location)) {
            return;
        }

        $this->ancestorIndexer->indexSingleForParentLocation($location);
    }

    private function isRootLocation(Location $location): bool
    {
        return $location->contentId === 0;
    }
}
