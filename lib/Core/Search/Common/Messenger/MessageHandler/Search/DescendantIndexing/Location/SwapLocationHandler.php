<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\Location;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\DescendantIndexing\AncestorIndexer;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Location\SwapLocation;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function sprintf;

final class SwapLocationHandler
{
    public function __construct(
        private readonly LocationHandler $locationHandler,
        private readonly AncestorIndexer $ancestorIndexer,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(SwapLocation $message): void
    {
        $this->reindexForLocation($message->location1Id);
        $this->reindexForLocation($message->location2Id);
    }

    private function reindexForLocation(int $locationId): void
    {
        try {
            $location = $this->locationHandler->load($locationId);
        } catch (NotFoundException) {
            $this->logger->info(
                sprintf(
                    '%s: Location #%d is gone, aborting',
                    $this::class,
                    $locationId,
                ),
            );

            return;
        }

        $this->ancestorIndexer->indexSingle($location);
    }
}
