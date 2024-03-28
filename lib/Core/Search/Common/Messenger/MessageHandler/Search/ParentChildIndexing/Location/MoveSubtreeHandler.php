<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\ParentChildIndexing\Location;

use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\MessageHandler\Search\ParentChildIndexing\AncestorIndexer;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Netgen\IbexaSearchExtra\Core\Search\Common\Messenger\Message\Search\Location\MoveSubtree;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function sprintf;

final class MoveSubtreeHandler
{
    public function __construct(
        private readonly LocationHandler $locationHandler,
        private readonly AncestorIndexer $ancestorIndexer,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(MoveSubtree $message): void
    {
        try {
            $location = $this->locationHandler->load(
                $message->locationId,
            );

            $this->ancestorIndexer->indexSingle($location);
        } catch (NotFoundException) {
            $this->logger->info(
                sprintf(
                    '%s: Location #%d is gone, aborting',
                    $this::class,
                    $message->locationId,
                ),
            );
        }

        try {
            $location = $this->locationHandler->load(
                $message->oldParentLocationId,
            );

            $this->ancestorIndexer->indexSingleForParentLocation($location);
        } catch (NotFoundException) {
            $this->logger->info(
                sprintf(
                    '%s: Old parent Location #%d is gone, aborting',
                    $this::class,
                    $message->locationId,
                ),
            );
        }
    }
}
